<?php

namespace App\Services\Bot;

use App\Models\Bot\BotBuyAttempt;
use App\Models\Bot\BotOrder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Persists one bot_buy_attempts row (plus a `bot-buy-attempts` log line) for
 * every BotBuyOrchestrator run.
 *
 * Motivation: a trigger that bought nothing used to return null and leave only
 * a `bot.orchestrator.no_order` log line behind, so a free balance sitting idle
 * for days was unexplainable after the fact. Every attempt now carries its own
 * wallet snapshot, the gate it had to clear and the per-currency verdict.
 *
 * Recording is best-effort: a failure here must never abort a real buy.
 */
class BotBuyAttemptRecorder
{
    /**
     * @param array<string, mixed> $describe BotBuyOrchestrator::describe() payload
     */
    public function record(
        int $userId,
        string $triggeredBy,
        array $describe,
        ?BotOrder $order = null,
        ?int $sellOrderId = null,
        ?Throwable $exception = null,
    ): void {
        $outcome = match (true) {
            $exception !== null => BotBuyAttempt::OUTCOME_EXCEPTION,
            $order !== null     => BotBuyAttempt::OUTCOME_ORDER_CREATED,
            default             => BotBuyAttempt::OUTCOME_BLOCKED,
        };

        $wallet  = $describe['wallet'] ?? [];
        $gate    = $describe['gate'] ?? [];
        $totals  = $describe['totals'] ?? [];
        $message = $this->message($describe, $order, $exception);

        try {
            BotBuyAttempt::create([
                'user_id'            => $userId,
                'bot_order_id'       => $order?->id,
                'bot_sell_order_id'  => $sellOrderId,
                'triggered_by'       => $triggeredBy,
                'outcome'            => $outcome,
                'reason_code'        => $describe['reason'] ?? null,
                'reason_message'     => $message,
                'balance'            => $wallet['balance'] ?? '0',
                'locked_balance'     => $wallet['locked_balance'] ?? '0',
                'free_balance'       => $wallet['free_balance'] ?? '0',
                'gate_amount'        => $gate['amount'] ?? null,
                'gate_kind'          => $gate['kind'] ?? null,
                'total_allocated'    => $totals['total_allocated'] ?? '0',
                'allocated_count'    => count($describe['allocations'] ?? []),
                'skipped_count'      => count($describe['skipped'] ?? []),
                'out_of_range_count' => count($describe['out_of_range'] ?? []),
                'unpriced_count'     => count($describe['unpriced'] ?? []),
                'details'            => $describe,
                'exception'          => $exception === null ? null : sprintf(
                    '%s: %s @ %s:%d',
                    $exception::class,
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                ),
            ]);
        } catch (Throwable $e) {
            Log::channel('bot-buy-attempts')->error('bot.buy_attempt.persist_failed', [
                'user_id'      => $userId,
                'triggered_by' => $triggeredBy,
                'outcome'      => $outcome,
                'error'        => $e->getMessage(),
            ]);
        }

        Log::channel('bot-buy-attempts')->info("bot.buy_attempt.{$outcome}", [
            'user_id'           => $userId,
            'triggered_by'      => $triggeredBy,
            'bot_order_id'      => $order?->id,
            'bot_sell_order_id' => $sellOrderId,
            'reason'            => $describe['reason'] ?? null,
            'message'           => $message,
            'balance'           => $wallet['balance'] ?? null,
            'locked'            => $wallet['locked_balance'] ?? null,
            'free'              => $wallet['free_balance'] ?? null,
            'gate'              => $gate['amount'] ?? null,
            'gate_kind'         => $gate['kind'] ?? null,
            'allocated'         => count($describe['allocations'] ?? []),
            'skipped'           => count($describe['skipped'] ?? []),
            'out_of_range'      => count($describe['out_of_range'] ?? []),
            'unpriced'          => count($describe['unpriced'] ?? []),
            'exception'         => $exception?->getMessage(),
        ]);
    }

    /**
     * The sentence an admin reads. Each block reason names the number that
     * failed, so "nothing was bought" is never left unexplained.
     *
     * @param array<string, mixed> $describe
     */
    public function message(array $describe, ?BotOrder $order = null, ?Throwable $exception = null): string
    {
        if ($exception !== null) {
            return 'اجرای خرید با خطای سیستمی متوقف شد: '.$exception->getMessage();
        }

        $free = formatNumberTrimZeros((string) ($describe['wallet']['free_balance'] ?? '0'));
        $gate = formatNumberTrimZeros((string) ($describe['gate']['amount'] ?? '0'));

        if ($order !== null) {
            return sprintf(
                'با %s USDT از موجودی آزاد، سفارش خرید #%d روی %d ارز ثبت شد.',
                formatNumberTrimZeros((string) ($describe['totals']['total_allocated'] ?? '0')),
                $order->id,
                count($describe['allocations'] ?? []),
            );
        }

        return match ($describe['reason'] ?? null) {
            'auto_trade_disabled' => sprintf(
                'ربات این کاربر در لحظه تلاش خاموش بود، پس %s USDT موجودی آزاد وارد پروسه خرید نشد.',
                $free,
            ),
            'bot_globally_disabled' => 'ربات به صورت سراسری غیرفعال است (تنظیمات کلی ربات).',
            'no_bot_wallet' => 'برای این کاربر کیف پول ربات ساخته نشده است؛ هنوز واریزی به ربات نداشته.',
            'insufficient_free_balance' => ($describe['gate']['kind'] ?? null) === 'buy_floor'
                ? sprintf(
                    'موجودی آزاد کاربر %s USDT است و از حداقل خرید ارزان‌ترین ارز در بازه (%s USDT) کمتر است، پس هیچ ارزی قابل خرید نیست.',
                    $free,
                    $gate,
                )
                : sprintf(
                    'موجودی آزاد کاربر %s USDT است و از حداقل واریز خالص (%s USDT) کمتر است. این تریگر روی حداقل واریز گیت می‌خورد، نه روی کف خرید ارزان‌ترین ارز.',
                    $free,
                    $gate,
                ),
            'no_eligible_signals' => count($describe['out_of_range'] ?? []) > 0
                ? sprintf(
                    'قیمت لحظه‌ای هر %d سیگنال فعال بیرون از بازه کف/سقف خودش بود، پس %s USDT موجودی آزاد جایی برای خرید نداشت.',
                    count($describe['out_of_range']),
                    $free,
                )
                : 'هیچ سیگنال فعالی برای خرید وجود نداشت.',
            'no_buyable_allocation' => sprintf(
                'موجودی آزاد %s USDT بین سیگنال‌ها تقسیم شد، اما سهم هیچ ارزی به حداقل خرید قابل‌قبول آن نرسید (یا سقف تخصیص ارزها پر بود).',
                $free,
            ),
            default => 'خرید با موجودی آزاد این کاربر امکان‌پذیر نبود.',
        };
    }
}
