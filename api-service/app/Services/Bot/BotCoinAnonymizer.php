<?php

namespace App\Services\Bot;

use App\Models\Bot\BotBuyExecution;

/**
 * Hides the real identity of a user's bot holdings behind stable, per-user
 * generic labels ("ارز ۱", "ارز ۲", ...) so the dashboard never exposes which
 * coins the auto-trade bot bought.
 *
 * The mapping is derived from every currency the user's bot has ever
 * successfully bought (BOUGHT or later CLOSED after a cancel), independent
 * of any date filter, ordered by the earliest execution of each currency.
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
            ->whereIn('bot_buy_executions.status', BotBuyExecution::successfulBuyStatuses())
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
