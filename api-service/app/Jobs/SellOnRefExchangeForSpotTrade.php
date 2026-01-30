<?php

namespace App\Jobs;

use App\Models\SpotTrade;
use App\Services\Exchanges\DTO\SpotRefExchangeSellRequestDTO;
use App\Services\Exchanges\ExchangeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job to sell coins on reference exchange when a bot trade occurs in spot trading.
 *
 * This job is dispatched when:
 * - A spot trade is completed
 * - One side of the trade is a bot (source = BOT)
 * - The user is selling coins to the bot (bot side = BUY)
 *
 * The job will sell the same quantity of coins on the reference exchange
 * to maintain balance consistency.
 */
class SellOnRefExchangeForSpotTrade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly int $spotTradeId,
        private readonly int $marketId,
        private readonly string $quantity
    ) {}

    public function handle(ExchangeService $exchangeService): void
    {
        try {
            $spotTrade = SpotTrade::find($this->spotTradeId);

            if (!$spotTrade) {
                Log::channel('spot-ref-exchange')->warning("SpotTrade not found for ref exchange sell", [
                    'spot_trade_id' => $this->spotTradeId,
                ]);
                return;
            }

            Log::channel('spot-ref-exchange')->info("Starting ref exchange sell for spot trade", [
                'spot_trade_id' => $this->spotTradeId,
                'market_id' => $this->marketId,
                'quantity' => $this->quantity,
            ]);

            $response = $exchangeService->sellForSpot(
                resolve(SpotRefExchangeSellRequestDTO::class)
                    ->setSpotTradeId($this->spotTradeId)
                    ->setMarketId($this->marketId)
                    ->setQuantity($this->quantity)
            );

            if ($response->isDone()) {
                Log::channel('spot-ref-exchange')->info("Ref exchange sell completed successfully for spot trade", [
                    'spot_trade_id' => $this->spotTradeId,
                    'market_id' => $this->marketId,
                    'quantity' => $this->quantity,
                ]);
            } else {
                Log::channel('spot-ref-exchange')->error("Ref exchange sell failed for spot trade", [
                    'spot_trade_id' => $this->spotTradeId,
                    'market_id' => $this->marketId,
                    'quantity' => $this->quantity,
                    'error_message' => $response->getErrorMessage(),
                    'error_code' => $response->getErrorCode(),
                ]);
            }
        } catch (Throwable $e) {
            Log::channel('spot-ref-exchange')->error("Exception during ref exchange sell for spot trade", [
                'spot_trade_id' => $this->spotTradeId,
                'market_id' => $this->marketId,
                'quantity' => $this->quantity,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('spot-ref-exchange')->critical("Job failed permanently for spot trade ref exchange sell", [
            'spot_trade_id' => $this->spotTradeId,
            'market_id' => $this->marketId,
            'quantity' => $this->quantity,
            'exception' => $exception->getMessage(),
        ]);
    }
}
