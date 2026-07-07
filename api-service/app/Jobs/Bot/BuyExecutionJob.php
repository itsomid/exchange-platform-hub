<?php

namespace App\Jobs\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotWallet;
use App\Services\Bot\BotOrderStatusService;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
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
 * (CoinEx, omnibus account). On a successful (full or partial) fill the
 * execution moves to BOUGHT and an OpenSellOrdersJob is dispatched to fan out
 * the limit sells. Market orders never rest on the book, so a PARTIAL result
 * is itself a terminal fill (the unfilled remainder was auto-canceled by the
 * exchange) and is treated the same as FILLED.
 *
 * CoinEx's place-order response can race its own matching engine: a market
 * order that in fact filled a moment later can still come back as `open` in
 * that very first response. We poll the authoritative order-status endpoint
 * briefly before giving up, and if it's still unresolved we let the queue
 * retry — but a resumed attempt (status already BUYING with a real
 * exchange_order_id) only ever re-checks that same order, it NEVER places a
 * second market buy.
 *
 * A pure transport failure (DNS/connection/timeout — the request never
 * reached CoinEx, so no exchange_order_id ever came back) is different: no
 * order could possibly have been created, so it IS safe to retry the buy
 * itself from scratch. The queue retries this exactly like a fresh attempt.
 *
 * Any genuinely terminal non-fill outcome is resolved synchronously right
 * here (locked balance released, row marked FAILED) instead of throwing +
 * relying on the queue's retry/failed() hook for the *final* resolution:
 * since the row is flipped to BUYING before the exchange call, blindly
 * bailing out on retries used to leave the execution permanently stuck with
 * funds locked and no sell orders ever opened. The failed() hook below only
 * remains as a safety net once retries for an unresolved/unreachable order
 * are exhausted, or for genuinely unexpected exceptions (DB errors etc.).
 *
 * Idempotent on retries: acts on rows in PENDING (fresh attempt), in BUYING
 * with no exchange_order_id yet (previous attempt never reached the
 * exchange — safe to re-buy), or in BUYING with an exchange_order_id already
 * recorded (resumed status check only, never re-buys).
 *
 * Retryable outcomes (awaiting_fill / transport_error) record their reason on
 * the row via `failure_reason` *without* changing status away from BUYING, so
 * activationStatus() can tell the frontend "still in flight, but on a retry"
 * instead of the user having to toggle the bot off/on to try again. The
 * retry budget below (tries × backoff) gives this roughly 8-9 minutes of
 * automatic retries before a persistent failure is finally given up on.
 */
class BuyExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 12;
    public array $backoff = [5, 10, 15, 30, 45, 60];

    public function __construct(public readonly int $executionId) {}

    public function handle(ExchangeContract $exchange): void
    {
        $execution = BotBuyExecution::with('currency')->find($this->executionId);
        if (! $execution) {
            return;
        }

        // Resumed attempt: a previous run already placed a real order on
        // CoinEx but couldn't confirm a terminal fill in time. Only re-check
        // that same order — never place a second market buy.
        if ($execution->status === BotBuyExecution::STATUS_BUYING && $execution->exchange_order_id) {
            $market = strtoupper((string) $execution->currency->symbol).'USDT';
            $result = $exchange->getOrder($market, (string) $execution->exchange_order_id);
            $this->resolveResult($execution, $market, $result);
            return;
        }

        // A fresh attempt starts from PENDING. A resumed attempt whose
        // previous placeMarketBuy() call failed at the transport level (no
        // exchange_order_id was ever returned) is also eligible: nothing was
        // created on the exchange, so re-buying from scratch is safe.
        $isFreshAttempt          = $execution->status === BotBuyExecution::STATUS_PENDING;
        $isResumableAfterNoOrder = $execution->status === BotBuyExecution::STATUS_BUYING && ! $execution->exchange_order_id;

        if (! $isFreshAttempt && ! $isResumableAfterNoOrder) {
            return;
        }

        if ($isFreshAttempt) {
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
        }

        $market = strtoupper((string) $execution->currency->symbol).'USDT';
        $result = $exchange->placeMarketBuy($market, (string) $execution->allocated_usdt);

        // Market orders settle almost instantly, but the place-order response
        // can race the matching engine and reflect a pre-fill OPEN snapshot.
        // Give it a brief moment and re-check via the authoritative
        // order-status endpoint before falling back to the slower
        // queue-retry path.
        if ($result->status === ExchangeOrderStatus::OPEN && $result->exchangeOrderId !== null) {
            $result = $this->pollForFill($exchange, $market, $result->exchangeOrderId);
        }

        $this->resolveResult($execution, $market, $result);
    }

    /**
     * Briefly re-check a just-placed order a few times before letting the
     * caller fall back to the slower queue-retry path.
     */
    private function pollForFill(ExchangeContract $exchange, string $market, string $orderId): ExchangeOrderResult
    {
        $result = new ExchangeOrderResult($orderId, ExchangeOrderStatus::OPEN);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            usleep(700_000);
            try {
                $result = $exchange->getOrder($market, $orderId);
            } catch (Throwable) {
                break;
            }
            if ($result->status !== ExchangeOrderStatus::OPEN) {
                break;
            }
        }

        return $result;
    }

    /**
     * Decide the outcome of a buy attempt (fresh or resumed) from an
     * ExchangeOrderResult: mark BOUGHT and fan out sells, let the job retry
     * (order still genuinely open), or fail the execution for good.
     */
    private function resolveResult(BotBuyExecution $execution, string $market, ExchangeOrderResult $result): void
    {
        $isTerminalFillStatus = in_array($result->status, [ExchangeOrderStatus::FILLED, ExchangeOrderStatus::PARTIAL], true);
        $hasFill = $isTerminalFillStatus
            && bccomp($result->filledAmount, '0', 8) > 0
            && bccomp($result->avgPrice, '0', 8) > 0;

        if ($hasFill) {
            $execution->update([
                'status'               => BotBuyExecution::STATUS_BOUGHT,
                'filled_amount'        => $result->filledAmount,
                'avg_buy_price'        => $result->avgPrice,
                'buy_ref_exchange_fee' => $result->exchangeFee,
                'exchange_order_id'    => $result->exchangeOrderId,
                // Clear any transient retry note recorded by an earlier
                // failed attempt — this one ultimately succeeded.
                'failure_reason'       => null,
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
            return;
        }

        // A real order exists and might still be settling. Persist its id
        // (if not already) and let the queue retry — the resumed attempt at
        // the top of handle() will only re-check it, never re-buy.
        if ($result->status === ExchangeOrderStatus::OPEN && $result->exchangeOrderId !== null) {
            $reason = sprintf('coinex.buy.awaiting_fill market=%s order=%s', $market, $result->exchangeOrderId);

            $execution->update([
                'exchange_order_id' => $result->exchangeOrderId,
                'failure_reason'    => mb_substr($reason, 0, 250),
            ]);

            Log::warning('coinex.buy.awaiting_fill', [
                'execution_id'      => $execution->id,
                'market'            => $market,
                'exchange_order_id' => $result->exchangeOrderId,
            ]);

            throw new RuntimeException($reason);
        }

        // Pure transport failure — the request never reached CoinEx (DNS
        // failure, connection timeout, etc.), so no order could have been
        // created. Safe, and desirable, to retry the buy itself from
        // scratch instead of failing the execution outright.
        if ($result->status === ExchangeOrderStatus::FAILED && $result->errorCode === 'TRANSPORT') {
            $reason = sprintf(
                'coinex.buy.transport_error market=%s msg=%s',
                $market,
                $result->errorMessage ?? 'unknown transport error',
            );

            // Status stays BUYING (still "in flight" for activationStatus),
            // but the reason is recorded so the frontend can tell the user
            // this is an automatic retry after a temporary issue.
            $execution->update(['failure_reason' => mb_substr($reason, 0, 250)]);

            Log::warning('coinex.buy.transport_retry', [
                'execution_id' => $execution->id,
                'market'       => $market,
                'error'        => $result->errorMessage,
            ]);

            throw new RuntimeException($reason);
        }

        $reason = $isTerminalFillStatus
            ? sprintf(
                'coinex.buy.invalid_fill market=%s status=%s filled=%s price=%s',
                $market,
                $result->status->value,
                $result->filledAmount,
                $result->avgPrice,
            )
            : sprintf(
                'coinex.buy.failed market=%s code=%s msg=%s',
                $market,
                $result->errorCode ?? 'n/a',
                $result->errorMessage ?? 'no error message',
            );

        Log::error($reason, [
            'execution_id'      => $execution->id,
            'exchange_order_id' => $result->exchangeOrderId,
            'status'            => $result->status->value,
        ]);

        $this->releaseAndFail($execution->id, $reason, $result->exchangeOrderId);
    }

    public function failed(Throwable $exception): void
    {
        $this->releaseAndFail($this->executionId, $exception->getMessage(), null);
    }

    /**
     * Release the locked USDT principal back to the wallet and mark the
     * execution FAILED. Safe to call more than once (re-checks status under
     * lock) and safe from both the normal non-fill path and the failed()
     * safety-net hook.
     */
    private function releaseAndFail(int $executionId, string $reason, ?string $exchangeOrderId): void
    {
        $botOrderId = DB::transaction(function () use ($executionId, $reason, $exchangeOrderId) {
            $execution = BotBuyExecution::where('id', $executionId)
                ->lockForUpdate()
                ->first();
            if (! $execution) {
                return null;
            }
            if (in_array($execution->status, [BotBuyExecution::STATUS_BOUGHT, BotBuyExecution::STATUS_FAILED, BotBuyExecution::STATUS_SKIPPED], true)) {
                return $execution->bot_order_id;
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
                'status'            => BotBuyExecution::STATUS_FAILED,
                'exchange_order_id' => $exchangeOrderId ?? $execution->exchange_order_id,
                'failure_reason'    => mb_substr($reason, 0, 250),
            ]);

            return $execution->bot_order_id;
        });

        // If this was the last non-bought signal of the order, settle the
        // order itself to FAILED instead of leaving it stuck at PENDING.
        if ($botOrderId) {
            app(BotOrderStatusService::class)->finalizeIfAllFailed((int) $botOrderId);
        }
    }
}
