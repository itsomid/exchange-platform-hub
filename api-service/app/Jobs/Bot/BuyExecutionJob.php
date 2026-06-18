<?php

namespace App\Jobs\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotWallet;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Executes a single bot_buy_execution as a MARKET BUY on the reference exchange
 * (CoinEx, omnibus account). On a successful fill the execution moves to BOUGHT
 * and an OpenSellOrdersJob is dispatched to fan out the limit sells. On any
 * failure the locked balance is released by the failed() hook.
 *
 * Idempotent on retries: only acts on rows still in PENDING.
 */
class BuyExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $executionId) {}

    public function handle(ExchangeContract $exchange): void
    {
        $execution = BotBuyExecution::with('currency')->find($this->executionId);
        if (! $execution || $execution->status !== BotBuyExecution::STATUS_PENDING) {
            return;
        }

        $advanced = DB::transaction(function () use ($execution) {
            $locked = BotBuyExecution::where('id', $execution->id)
                ->lockForUpdate()
                ->first();
            if (! $locked || $locked->status !== BotBuyExecution::STATUS_PENDING) {
                return false;
            }
            $locked->update(['status' => BotBuyExecution::STATUS_BUYING]);
            return true;
        });
        if (! $advanced) {
            return;
        }

        $market = strtoupper((string) $execution->currency->symbol).'USDT';
        $result = $exchange->placeMarketBuy($market, (string) $execution->allocated_usdt);

        if ($result->status !== ExchangeOrderStatus::FILLED) {
            throw new RuntimeException(sprintf(
                'coinex.buy.failed market=%s code=%s msg=%s',
                $market,
                $result->errorCode ?? 'n/a',
                $result->errorMessage ?? 'no error message',
            ));
        }

        if (bccomp($result->filledAmount, '0', 8) <= 0 || bccomp($result->avgPrice, '0', 8) <= 0) {
            throw new RuntimeException('coinex.buy.invalid_fill market='.$market);
        }

        $execution->update([
            'status'            => BotBuyExecution::STATUS_BOUGHT,
            'filled_amount'     => $result->filledAmount,
            'avg_buy_price'     => $result->avgPrice,
            'exchange_fee'      => $result->exchangeFee,
            'exchange_order_id' => $result->exchangeOrderId,
        ]);

        Log::info('bot.buy.execution.filled', [
            'execution_id'    => $execution->id,
            'market'          => $market,
            'filled_amount'   => $result->filledAmount,
            'avg_buy_price'   => $result->avgPrice,
            'exchange_fee'    => $result->exchangeFee,
            'exchange_order'  => $result->exchangeOrderId,
        ]);

        OpenSellOrdersJob::dispatch($execution->id)->onQueue('bot-sell');
    }

    public function failed(Throwable $exception): void
    {
        DB::transaction(function () use ($exception) {
            $execution = BotBuyExecution::where('id', $this->executionId)
                ->lockForUpdate()
                ->first();
            if (! $execution) {
                return;
            }
            if (in_array($execution->status, [BotBuyExecution::STATUS_BOUGHT, BotBuyExecution::STATUS_FAILED, BotBuyExecution::STATUS_SKIPPED], true)) {
                return;
            }

            $wallet = BotWallet::where('user_id', $execution->botOrder->user_id)
                ->lockForUpdate()
                ->first();
            if ($wallet) {
                $newLocked = bcsub((string) $wallet->locked_balance, (string) $execution->allocated_usdt, 8);
                if (bccomp($newLocked, '0', 8) < 0) {
                    $newLocked = '0';
                }
                $wallet->update(['locked_balance' => $newLocked]);
            }

            $execution->update([
                'status'         => BotBuyExecution::STATUS_FAILED,
                'failure_reason' => mb_substr($exception->getMessage(), 0, 250),
            ]);
        });
    }
}
