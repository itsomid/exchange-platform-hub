<?php

namespace App\Services\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use Illuminate\Support\Facades\DB;

/**
 * Appends read-only system notes to bot_orders.description (one note per
 * affected execution, prefixed with [SYMBOL]). Admin-editable notes live in
 * bot_orders.admin_description and are never touched here.
 */
class BotOrderDescriptionService
{
    private const SEP = ' || ';

    public function appendSystemNote(BotBuyExecution $execution, string $note): void
    {
        $note = trim($note);
        if ($note === '') {
            return;
        }

        $execution->loadMissing('currency');
        $symbol = strtoupper((string) ($execution->currency?->symbol ?? ''));
        $prefixed = $symbol !== '' ? "[{$symbol}] {$note}" : $note;

        $orderId = (int) $execution->bot_order_id;
        if ($orderId <= 0) {
            return;
        }

        DB::transaction(function () use ($orderId, $prefixed) {
            $order = BotOrder::where('id', $orderId)->lockForUpdate()->first();
            if (! $order) {
                return;
            }

            $existing = (string) ($order->description ?? '');
            if ($existing !== '' && str_contains($existing, $prefixed)) {
                return;
            }

            $order->update([
                'description' => trim($existing === '' ? $prefixed : $existing.self::SEP.$prefixed),
            ]);
        });
    }
}
