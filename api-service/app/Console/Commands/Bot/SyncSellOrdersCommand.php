<?php

namespace App\Console\Commands\Bot;

use App\Events\Bot\BotSellOrderFilled;
use App\Models\Bot\BotSellOrder;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
 * Each run polls at most --limit orders, continuing from where the previous
 * run stopped (rotating cursor persisted in cache) and wrapping around at the
 * end of the list. This guarantees every OPEN order is eventually polled even
 * when the OPEN backlog is much larger than --limit — old far-target orders
 * can no longer starve newer ones.
 *
 * Scheduled every 5 minutes via routes/console.php with --limit=500
 * (withoutOverlapping).
 */
class SyncSellOrdersCommand extends Command
{
    private const CURSOR_CACHE_KEY = 'bot:sync-sell-orders:cursor';

    protected $signature = 'bot:sync-sell-orders {--limit=500 : Max orders to poll per run}';

    protected $description = 'Poll reference exchange for fill/cancel status of OPEN bot sell orders';

    public function handle(ExchangeContract $exchange): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $this->info('bot:sync-sell-orders starting limit='.$limit);

        $orders = $this->nextBatch($limit);
        $batchStartId = $orders->isEmpty() ? null : $orders->first()->id;
        $batchEndId = $orders->isEmpty() ? null : $orders->last()->id;

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
                // Also persist the sell-leg exchange fee so it's available for audit and settlement.
                $stillOpen = DB::transaction(function () use ($sellOrder, $result) {
                    $fresh = BotSellOrder::where('id', $sellOrder->id)->lockForUpdate()->first();
                    if (! $fresh || $fresh->status !== BotSellOrder::STATUS_OPEN) {
                        return false;
                    }
                    $fresh->update(['sell_ref_exchange_fee' => $result->exchangeFee ?? '0']);
                    $sellOrder->sell_ref_exchange_fee = $result->exchangeFee ?? '0';
                    return true;
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

        $summary = "bot:sync-sell-orders checked={$checked} filled={$filled} canceled={$canceled} errors={$errors} batch={$orders->count()} start_id={$batchStartId} end_id={$batchEndId}";
        $this->info($summary);
        // Log::channel('smart-bot')->info('bot.sync.sell.done', [
        //     'checked'  => $checked,
        //     'filled'   => $filled,
        //     'canceled' => $canceled,
        //     'errors'   => $errors,
        //     'batch'    => $orders->count(),
        //     'cursor'   => (int) Cache::get(self::CURSOR_CACHE_KEY, 0),
        // ]);

        return self::SUCCESS;
    }

    /**
     * Fetch the next window of OPEN orders after the cached cursor, wrapping
     * around to the beginning of the list when the end is reached, so the
     * whole backlog is covered across consecutive runs.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BotSellOrder>
     */
    private function nextBatch(int $limit)
    {
        $base = fn () => BotSellOrder::query()
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->whereNotNull('exchange_order_id')
            ->with(['botBuyExecution', 'botBuyExecution.currency'])
            ->orderBy('id');

        $cursor = (int) Cache::get(self::CURSOR_CACHE_KEY, 0);

        $orders = $base()->where('id', '>', $cursor)->limit($limit)->get();

        // Reached the end of the list: wrap around and take the remainder
        // from the beginning (rows we already fetched are excluded by id).
        if ($orders->count() < $limit && $cursor > 0) {
            $remaining = $limit - $orders->count();
            $orders = $orders->concat(
                $base()->where('id', '<=', $cursor)->limit($remaining)->get(),
            );
        }

        Cache::forever(self::CURSOR_CACHE_KEY, $orders->isEmpty() ? 0 : (int) $orders->last()->id);

        return $orders;
    }
}
