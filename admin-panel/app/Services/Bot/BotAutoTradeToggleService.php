<?php

namespace App\Services\Bot;

use App\Models\Admin;
use App\Models\Bot\BotAutoTradeEvent;
use App\Models\Bot\BotUserSettings;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BotAutoTradeToggleService
{
    /**
     * @param  array<string, mixed>  $meta
     * @return bool true when the flag actually changed
     */
    public function setEnabled(
        int $userId,
        bool $enabled,
        string $source,
        string $reasonCode,
        string $reason,
        ?string $actorType = null,
        ?int $actorId = null,
        ?string $actorLabel = null,
        array $meta = [],
    ): bool {
        $settings = BotUserSettings::firstOrCreate(
            ['user_id' => $userId],
            ['auto_trade_enabled' => false, 'reinvest_enabled' => false],
        );

        if ((bool) $settings->auto_trade_enabled === $enabled) {
            return false;
        }

        $settings->auto_trade_enabled = $enabled;
        $settings->save();

        $user      = User::query()->find($userId);
        $userLabel = $this->userLabel($userId, $user);

        BotAutoTradeEvent::create([
            'user_id'     => $userId,
            'enabled'     => $enabled,
            'source'      => $source,
            'reason_code' => $reasonCode,
            'reason'      => $reason,
            'actor_type'  => $actorType,
            'actor_id'    => $actorId,
            'actor_label' => $actorLabel,
            'meta'        => $meta ?: null,
        ]);

        $this->writeLog(
            $userId,
            $userLabel,
            $enabled,
            $source,
            $reasonCode,
            $reason,
            $actorType,
            $actorId,
            $actorLabel,
            $meta,
        );

        return true;
    }

    public function setByAdmin(User $user, bool $enabled, Admin $admin): bool
    {
        $adminLabel = $admin->email ?: ($admin->mobile ?: 'admin#'.$admin->id);

        return $this->setEnabled(
            $user->id,
            $enabled,
            BotAutoTradeEvent::SOURCE_ADMIN,
            BotAutoTradeEvent::REASON_ADMIN_TOGGLE,
            $enabled
                ? 'ادمین سوئیچ خرید و فروش خودکار را از صفحه کاربر روشن کرد.'
                : 'ادمین سوئیچ خرید و فروش خودکار را از صفحه کاربر خاموش کرد.',
            BotAutoTradeEvent::ACTOR_ADMIN,
            $admin->id,
            $adminLabel,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function writeLog(
        int $userId,
        string $userLabel,
        bool $enabled,
        string $source,
        string $reasonCode,
        string $reason,
        ?string $actorType,
        ?int $actorId,
        ?string $actorLabel,
        array $meta,
    ): void {
        $action = $enabled ? 'روشن شد' : 'خاموش شد';
        $auto   = $source === BotAutoTradeEvent::SOURCE_SYSTEM ? ' به صورت خودکار' : '';

        $actorBit = '';
        if ($actorLabel && $actorType === BotAutoTradeEvent::ACTOR_ADMIN) {
            $actorBit = ' ادمین: '.$actorLabel.($actorId ? ' (#'.$actorId.')' : '').'.';
        }

        $message = sprintf(
            'بات کاربر #%d (%s)%s %s. روش: %s.%s دلیل: %s',
            $userId,
            $userLabel,
            $auto,
            $action,
            $this->methodPhrase($source, $reasonCode),
            $actorBit,
            $reason,
        );

        $context = [
            'event'       => 'bot.auto_trade.toggled',
            'user_id'     => $userId,
            'user'        => $userLabel,
            'enabled'     => $enabled,
            'source'      => $source,
            'reason_code' => $reasonCode,
            'reason'      => $reason,
            'actor_type'  => $actorType,
            'actor_id'    => $actorId,
            'actor_label' => $actorLabel,
            'meta'        => $meta,
        ];

        $logger = Log::channel('smart-bot');
        $enabled
            ? $logger->info($message, $context)
            : $logger->warning($message, $context);
    }

    private function methodPhrase(string $source, string $reasonCode): string
    {
        return match ($reasonCode) {
            BotAutoTradeEvent::REASON_USER_MANUAL => 'درخواست دستی کاربر از اپ',
            BotAutoTradeEvent::REASON_ADMIN_TOGGLE => 'ادمین از پنل مدیریت (سوئیچ خرید و فروش خودکار)',
            BotAutoTradeEvent::REASON_ADMIN_CANCEL_ALL => 'ادمین از پنل مدیریت (لغو همه سفارش‌ها و آزادسازی وجوه)',
            BotAutoTradeEvent::REASON_ORDER_ALL_BUYS_FAILED => 'سیستم (ناموفق بودن تمام خریدهای سفارش بات)',
            default => $source,
        };
    }

    private function userLabel(int $userId, ?User $user): string
    {
        if (! $user) {
            return 'id='.$userId;
        }

        $parts = array_filter([(string) $user->email, (string) $user->mobile]);

        return $parts !== [] ? implode(' | ', $parts) : 'id='.$userId;
    }
}
