<?php

namespace App\Services\Bot;

use App\Models\Bot\BotBuyExecution;

/**
 * Hides the real identity of a user's bot holdings behind stable, per-user
 * generic labels ("ارز ۱", "ارز ۲", ...) so the dashboard never exposes which
 * coins the auto-trade bot bought.
 *
 * The mapping is derived from every currency the user's bot has ever
 * successfully BOUGHT (independent of any date filter), ordered by the
 * earliest execution of each currency — i.e. the first signal the bot ever
 * bought for this user becomes "ارز ۱", the second "ارز ۲", and so on. Since
 * both the allocations and per-coin endpoints only ever display BOUGHT
 * executions, indices always start at 1 and stay contiguous, and the same
 * coin resolves to the same label across both endpoints.
 */
class BotCoinAnonymizer
{
    private const DIGIT_MAP = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    /**
     * @return array<int, int> currency_id => 1-based display index
     */
    public function mapForUser(int $userId): array
    {
        $currencyIds = BotBuyExecution::query()
            ->select('bot_buy_executions.currency_id')
            ->selectRaw('MIN(bot_buy_executions.id) as first_execution_id')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $userId)
            ->where('bot_buy_executions.status', BotBuyExecution::STATUS_BOUGHT)
            ->groupBy('bot_buy_executions.currency_id')
            ->orderBy('first_execution_id')
            ->pluck('bot_buy_executions.currency_id');

        $map = [];
        foreach ($currencyIds as $index => $currencyId) {
            $map[$currencyId] = $index + 1;
        }

        return $map;
    }

    public function label(int $index): string
    {
        return 'ارز '.strtr((string) $index, self::DIGIT_MAP);
    }
}
