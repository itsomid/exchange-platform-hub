<?php

namespace App\Console\Commands\Bot;

use App\Events\Bot\BotSellOrderFilled;
use App\Models\Bot\BotSellOrder;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Polls the reference exchange (CoinEx) for the status of every OPEN bot sell
 * order that has an exchange_order_id and reacts to fills / cancellations.
 *
 * - FILLED  → dispatch BotSellOrderFilled with the realized fill data so
 *             HandleBotSellOrderFilled runs the settlement on bot-settlement queue.
 * - CANCELED → mark the local row CANCELED (settlement was already applied if the
 *              cancel originated locally; if it was canceled directly on the
 *              exchange we leave principal release to a manual reconcile path).
 *
 * Scheduled every minute via routes/console.php (withoutOverlapping).
 */
class SyncSellOrdersCommand extends Command
{
    protected $signature = 'bot:sync-sell-orders {--limit=100 : Max orders to poll per run}';

    protected $description = 'Poll reference exchange for fill/cancel status of OPEN bot sell orders';

    public function handle(ExchangeContract $exchange): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $orders = BotSellOrder::query()
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->whereNotNull('exchange_order_id')
            ->with(['botBuyExecution', 'botBuyExecution.currency'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $checked = 0;
        $filled  = 0;
        $canceled = 0;
        $errors   = 0;

        foreach ($orders as $sellOrder) {
            $currency = $sellOrder->botBuyExecution?->currency;
            if (! $currency) {
                continue;
            }
            $market = strtoupper((string) $currency->symbol).'USDT';

            try {
                $result = $exchange->getOrder($market, (string) $sellOrder->exchange_order_id);
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('bot.sync.order_lookup_failed', [
                    'sell_order_id'     => $sellOrder->id,
                    'exchange_order_id' => $sellOrder->exchange_order_id,
                    'market'            => $market,
                    'error'             => $e->getMessage(),
                ]);
                continue;
            }
            $checked++;

            if ($result->status === ExchangeOrderStatus::FILLED) {
                // Re-verify with row lock that we're still OPEN to keep this idempotent.
                $stillOpen = DB::transaction(function () use ($sellOrder) {
                    $fresh = BotSellOrder::where('id', $sellOrder->id)->lockForUpdate()->first();
                    return $fresh && $fresh->status === BotSellOrder::STATUS_OPEN;
                });
                if (! $stillOpen) {
                    continue;
                }

                BotSellOrderFilled::dispatch(
                    $sellOrder,
                    $result->filledAmount,
                    $result->avgPrice,
                    '0',                  // networkFee — none on spot fills
                    $result->exchangeFee, // exchangeFee in quote (USDT)
                    '0',                  // spreadFee — none on direct exchange fills
                );
                $filled++;
                continue;
            }

            if ($result->status === ExchangeOrderStatus::CANCELED) {
                // Defensive: only flip if still OPEN locally (otherwise the cancel
                // service has already settled it).
                DB::transaction(function () use ($sellOrder) {
                    $fresh = BotSellOrder::where('id', $sellOrder->id)->lockForUpdate()->first();
                    if ($fresh && $fresh->status === BotSellOrder::STATUS_OPEN) {
                        $fresh->update(['status' => BotSellOrder::STATUS_CANCELED]);
                    }
                });
                $canceled++;
            }
        }

        $this->info("bot:sync-sell-orders checked={$checked} filled={$filled} canceled={$canceled} errors={$errors}");

        return self::SUCCESS;
    }
}
