<?php

namespace App\Services\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use DateTimeInterface;

/**
 * Explains why a bot order / sell row is CANCELED (or a buy execution is CLOSED).
 * Prefers stored cancel_reason / cancel_source; infers from related rows for legacy data.
 */
class BotCanceledReason
{
    /**
     * @return array{kind: string, title: string, lines: list<string>, html: string}|null
     */
    public static function forSellOrder(
        BotSellOrder $sell,
        BotBuyExecution $execution,
        BotOrder $order,
        ?BotTradeSettlement $settlement = null,
    ): ?array {
        if ($sell->status !== 'CANCELED') {
            return null;
        }

        $settlement ??= $sell->settlement;
        $stored = trim((string) ($sell->cancel_reason ?? ''));
        if ($stored !== '') {
            return self::fromStored($stored, $execution, $order, $settlement);
        }

        return self::inferSellOrder($sell, $execution, $order, $settlement);
    }

    /**
     * @return array{kind: string, title: string, lines: list<string>, html: string}|null
     */
    public static function forBuyExecution(BotBuyExecution $execution, BotOrder $order): ?array
    {
        if ($execution->status !== 'CLOSED') {
            return null;
        }

        $who = match ($order->cancel_source) {
            BotOrder::CANCEL_SOURCE_ADMIN => 'از پنل ادمین',
            BotOrder::CANCEL_SOURCE_USER => 'توسط کاربر',
            default => 'به‌صورت دستی',
        };

        return self::make('closed', 'موقعیت بسته شد', [
            'خرید این ارز در صرافی مرجع موفق بود.',
            "بعد از لغو سفارش ({$who}) کوین فروخته شد و دیگر موقعیت بازی وجود ندارد.",
        ]);
    }

    /**
     * @return array{kind: string, title: string, lines: list<string>, html: string}
     */
    private static function fromStored(
        string $reason,
        BotBuyExecution $execution,
        BotOrder $order,
        ?BotTradeSettlement $settlement,
    ): array {
        $failure = trim((string) ($execution->failure_reason ?? ''));

        return match ($reason) {
            BotSellOrder::CANCEL_ADMIN => self::manualCancelExplain('admin', $order, $settlement),
            BotSellOrder::CANCEL_USER => self::manualCancelExplain('user', $order, $settlement),
            BotSellOrder::CANCEL_PLACE_FAILED => self::make(
                'place_never',
                'ثبت پله فروش روی صرافی ناموفق بود',
                array_merge(
                    ['خرید این ارز انجام شد، اما این پله فروش هرگز روی صرافی مرجع ثبت نشد و به‌صورت CANCELED در سیستم ماند.'],
                    self::failureLines($failure),
                ),
            ),
            BotSellOrder::CANCEL_PLACE_ROLLBACK => self::make(
                'place_rollback',
                'کنسل به‌خاطر شکست ثبت پله‌های بعدی',
                array_merge(
                    [
                        'این پله روی صرافی مرجع ثبت شده بود، اما ثبت پله بعدی شکست خورد.',
                        'برای جلوگیری از موقعیت ناقص، این سفارش فروش هم در صرافی کنسل شد.',
                    ],
                    self::failureLines($failure),
                ),
            ),
            BotSellOrder::CANCEL_EXCHANGE_SYNC => self::make(
                'exchange_sync',
                'کنسل شدن در صرافی مرجع',
                [
                    'سفارش فروش لیمیت روی صرافی مرجع کنسل شده و هنگام همگام‌سازی وضعیت، اینجا هم CANCELED شده است.',
                    'تسویه خودکار برای این پله ثبت نشده است.',
                    $order->status === 'CANCELED'
                        ? 'سفارش والد هم در وضعیت CANCELED است.'
                        : 'سفارش والد CANCELED نیست — سرمایه این پله ممکن است هنوز قفل باشد و نیاز به بررسی دستی داشته باشد.',
                ],
            ),
            default => self::make('unknown', 'لغو شده', [
                'دلیل ثبت‌شده: '.$reason,
            ]),
        };
    }

    /**
     * Fallback for rows created before cancel_reason existed.
     *
     * @return array{kind: string, title: string, lines: list<string>, html: string}
     */
    private static function inferSellOrder(
        BotSellOrder $sell,
        BotBuyExecution $execution,
        BotOrder $order,
        ?BotTradeSettlement $settlement,
    ): array {
        $failure = trim((string) ($execution->failure_reason ?? ''));
        $hasExchangeId = filled($sell->exchange_order_id);
        $execFailed = $execution->status === 'FAILED';

        if ($settlement) {
            return self::fromSettlement($settlement, $order);
        }

        if ($execFailed && ! $hasExchangeId) {
            return self::make(
                'place_never',
                'ثبت پله فروش روی صرافی ناموفق بود',
                array_merge(
                    [
                        'خرید این ارز انجام شد، اما این پله فروش هرگز روی صرافی مرجع ثبت نشد و به‌صورت CANCELED در سیستم ماند.',
                    ],
                    self::failureLines($failure),
                ),
            );
        }

        if ($execFailed && $hasExchangeId) {
            return self::make(
                'place_rollback',
                'کنسل به‌خاطر شکست ثبت پله‌های بعدی',
                array_merge(
                    [
                        'این پله روی صرافی مرجع ثبت شده بود، اما ثبت پله بعدی شکست خورد.',
                        'برای جلوگیری از موقعیت ناقص، این سفارش فروش هم در صرافی کنسل شد.',
                    ],
                    self::failureLines($failure),
                ),
            );
        }

        if ($hasExchangeId) {
            $parentCanceled = $order->status === 'CANCELED';

            return self::make(
                'exchange_sync',
                'کنسل شدن در صرافی مرجع',
                [
                    'سفارش فروش لیمیت روی صرافی مرجع کنسل شده و هنگام همگام‌سازی وضعیت، اینجا هم CANCELED شده است.',
                    'تسویه خودکار برای این پله ثبت نشده است.',
                    $parentCanceled
                        ? 'سفارش والد هم در وضعیت CANCELED است.'
                        : 'سفارش والد CANCELED نیست — سرمایه این پله ممکن است هنوز قفل باشد و نیاز به بررسی دستی داشته باشد.',
                ],
            );
        }

        return self::make(
            'unknown',
            'لغو شده',
            [
                'وضعیت این پله فروش CANCELED است اما از روی داده‌های موجود دلیل دقیق مشخص نیست.',
                $failure !== '' ? 'یادداشت اجرا: '.$failure : 'تسویه و شناسه سفارش صرافی موجود نیست.',
            ],
        );
    }

    /**
     * @return array{kind: string, title: string, lines: list<string>, html: string}|null
     */
    public static function forBotOrder(BotOrder $order): ?array
    {
        if ($order->status !== 'CANCELED') {
            return null;
        }

        $execs = $order->relationLoaded('buyExecutions') ? $order->buyExecutions : collect();
        $sells = $execs->flatMap(fn ($e) => $e->relationLoaded('sellOrders') ? $e->sellOrders : collect());
        $canceledSells = $sells->where('status', 'CANCELED')->count();
        $filledSells = $sells->where('status', 'FILLED')->count();
        $closedExecs = $execs->where('status', 'CLOSED')->count();
        $boughtExecs = $execs->whereIn('status', ['BOUGHT', 'CLOSED'])->count();
        $failedExecs = $execs->where('status', 'FAILED')->count();

        $who = match ($order->cancel_source) {
            BotOrder::CANCEL_SOURCE_ADMIN => 'از پنل ادمین',
            BotOrder::CANCEL_SOURCE_USER => 'توسط کاربر',
            default => 'از پنل ادمین یا توسط کاربر',
        };

        $lines = [
            "این سفارش ربات {$who} لغو شده است.",
            'خریدهایی که انجام شده بودند حفظ نمی‌شوند: پله‌های فروش باز کنسل می‌شوند و موجودی آزاد می‌گردد.',
        ];

        $canceledAt = self::formatTime($order->completed_at);
        if ($canceledAt !== null) {
            $lines[] = 'زمان لغو: '.$canceledAt;
        }
        if ($boughtExecs > 0) {
            $lines[] = "تعداد اجراهای خرید موفق: {$boughtExecs}";
        }
        if ($closedExecs > 0) {
            $lines[] = "اجراهای بسته‌شده پس از لغو: {$closedExecs}";
        }
        if ($canceledSells > 0) {
            $extra = $filledSells > 0 ? " (تکمیل‌شده قبل از لغو: {$filledSells})" : '';
            $lines[] = "پله‌های فروش کنسل‌شده: {$canceledSells}{$extra}";
        }
        if ($failedExecs > 0) {
            $lines[] = "توجه: {$failedExecs} اجرای خرید جداگانه FAILED است؛ علت آن با لغو دستی فرق دارد.";
        }

        return self::make('manual_cancel', 'سفارش ربات لغو شد', $lines);
    }

    /**
     * @return array{kind: string, title: string, lines: list<string>, html: string}
     */
    private static function manualCancelExplain(string $source, BotOrder $order, ?BotTradeSettlement $settlement): array
    {
        $who = $source === BotOrder::CANCEL_SOURCE_ADMIN ? 'از پنل ادمین' : 'توسط کاربر';
        $kind = $source === BotOrder::CANCEL_SOURCE_ADMIN
            ? BotSellOrder::CANCEL_ADMIN
            : BotSellOrder::CANCEL_USER;

        if ($settlement && self::isPositive($settlement->cancel_fee) && ! self::isPositive($settlement->gross_revenue)) {
            return self::make(
                'cancel_fee',
                'لغو دستی (بازگشت اصل سرمایه)',
                [
                    "خرید این ارز انجام شده بود، اما این پله فروش {$who} قبل از رسیدن به هدف لغو شد.",
                    'کوین در بازار فروخته نشد؛ اصل سرمایه با کسر کارمزد لغو به کاربر برگشت.',
                    'کارمزد لغو: '.formatNumberTrimZeros($settlement->cancel_fee).' USDT',
                ],
            );
        }

        $lines = [
            "خرید این ارز انجام شده بود، اما سفارش ربات {$who} لغو شد.",
            'پله فروش لیمیت در صرافی مرجع کنسل شد و موجودی با فروش مارکت / قیمت لحظه‌ای تسویه گردید.',
        ];

        $canceledAt = self::formatTime($order->completed_at);
        if ($canceledAt !== null) {
            $lines[] = 'زمان لغو سفارش: '.$canceledAt;
        }
        if ($settlement && self::isPositive($settlement->gross_revenue)) {
            $lines[] = 'مبلغ فروش (gross): '.formatNumberTrimZeros($settlement->gross_revenue).' USDT';
        }
        if ($settlement && self::isPositive($settlement->net_pnl)) {
            $lines[] = 'سود خالص تسویه: '.formatNumberTrimZeros($settlement->net_pnl).' USDT';
        } elseif ($settlement && self::isNegative($settlement->net_pnl)) {
            $lines[] = 'زیان خالص تسویه: '.formatNumberTrimZeros($settlement->net_pnl).' USDT';
        }

        return self::make($kind, 'لغو سفارش ربات پس از خرید', $lines);
    }

    /**
     * @return array{kind: string, title: string, lines: list<string>, html: string}
     */
    private static function fromSettlement(BotTradeSettlement $settlement, BotOrder $order): array
    {
        if (self::isPositive($settlement->cancel_fee) && ! self::isPositive($settlement->gross_revenue)) {
            return self::make(
                'cancel_fee',
                'لغو دستی (بازگشت اصل سرمایه)',
                [
                    'خرید این ارز انجام شده بود، اما این پله فروش قبل از رسیدن به هدف لغو شد.',
                    'کوین در بازار فروخته نشد؛ اصل سرمایه با کسر کارمزد لغو به کاربر برگشت.',
                    'کارمزد لغو: '.formatNumberTrimZeros($settlement->cancel_fee).' USDT',
                ],
            );
        }

        $lines = [
            'خرید این ارز انجام شده بود، اما سفارش ربات لغو شد (از پنل ادمین یا توسط کاربر).',
            'پله فروش لیمیت در صرافی مرجع کنسل شد و موجودی با فروش مارکت / قیمت لحظه‌ای تسویه گردید.',
        ];

        $canceledAt = self::formatTime($order->completed_at);
        if ($canceledAt !== null) {
            $lines[] = 'زمان لغو سفارش: '.$canceledAt;
        }
        if (self::isPositive($settlement->gross_revenue)) {
            $lines[] = 'مبلغ فروش (gross): '.formatNumberTrimZeros($settlement->gross_revenue).' USDT';
        }
        if (self::isPositive($settlement->net_pnl)) {
            $lines[] = 'سود خالص تسویه: '.formatNumberTrimZeros($settlement->net_pnl).' USDT';
        } elseif (self::isNegative($settlement->net_pnl)) {
            $lines[] = 'زیان خالص تسویه: '.formatNumberTrimZeros($settlement->net_pnl).' USDT';
        }

        return self::make('order_canceled', 'لغو سفارش ربات پس از خرید', $lines);
    }

    /**
     * @return list<string>
     */
    private static function failureLines(string $reason): array
    {
        if ($reason === '') {
            return ['دلیل دقیق در فیلد خطای اجرا ثبت نشده است.'];
        }

        $segments = array_values(array_filter(array_map('trim', explode('|', $reason))));
        $primary = $segments[0] ?? $reason;
        $lines = [];

        if (str_starts_with($primary, 'coinex.sell.place_failed')) {
            $lines[] = 'ثبت سفارش فروش لیمیت در صرافی مرجع شکست خورد.';
            if (preg_match('/market=([^\s]+)/', $primary, $m)) {
                $lines[] = 'بازار: '.$m[1];
            }
            if (preg_match('/code=([^\s]+)/', $primary, $m) && $m[1] !== 'n/a') {
                $lines[] = 'کد خطا: '.$m[1];
            }
            if (preg_match('/msg=(.+)$/', $primary, $m)) {
                $lines[] = 'پیام صرافی: '.trim($m[1]);
            }
        } elseif (str_starts_with($primary, 'Single target value')) {
            $lines[] = 'ارزش تنها پله باقی‌مانده از حداقل سفارش مجاز (P2P) کمتر بود، بنابراین فروش باز نشد.';
            $lines[] = $primary;
        } elseif ($primary === 'Collapse produced no viable targets') {
            $lines[] = 'پس از ادغام پله‌ها هیچ تارگت قابل‌فروشی باقی نماند.';
        } elseif ($primary === 'No active BotSignal for currency on sell-open') {
            $lines[] = 'در لحظه باز کردن فروش، سیگنال فعالی برای این ارز وجود نداشت.';
        } elseif ($primary === 'Missing sell_targets or filled_amount on sell-open') {
            $lines[] = 'تارگت فروش یا مقدار خریداری‌شده برای باز کردن پله‌ها موجود نبود.';
        } else {
            $lines[] = $primary;
        }

        foreach (array_slice($segments, 1) as $extra) {
            if ($extra === 'auto-liquidated') {
                $lines[] = 'موقعیت خریداری‌شده به‌صورت اضطراری در صرافی مرجع نقد شد تا کوین روی حساب گیر نکند.';
            } elseif (str_starts_with($extra, 'dispose_failed')) {
                $lines[] = 'نقدسازی اضطراری هم ناموفق بود — نیاز به بررسی اپراتور.';
            } elseif (str_starts_with($extra, 'liquidation_exchange_order_id=')) {
                $lines[] = 'شناسه سفارش نقدسازی: '.substr($extra, strlen('liquidation_exchange_order_id='));
            } else {
                $lines[] = $extra;
            }
        }

        return $lines;
    }

    /**
     * @param  list<string>  $lines
     * @return array{kind: string, title: string, lines: list<string>, html: string}
     */
    private static function make(string $kind, string $title, array $lines): array
    {
        $items = '';
        foreach ($lines as $line) {
            $items .= '<div class=\'mb-1\'>'.e($line).'</div>';
        }

        return [
            'kind'  => $kind,
            'title' => $title,
            'lines' => $lines,
            'html'  => '<div class=\'text-end\'><div class=\'fw-bold mb-1\'>'.e($title).'</div>'.$items.'</div>',
        ];
    }

    private static function isPositive(mixed $value): bool
    {
        return bccomp(self::num($value), '0', 8) > 0;
    }

    private static function isNegative(mixed $value): bool
    {
        return bccomp(self::num($value), '0', 8) < 0;
    }

    private static function num(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return is_numeric($value) ? (string) $value : '0';
    }

    private static function formatTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
