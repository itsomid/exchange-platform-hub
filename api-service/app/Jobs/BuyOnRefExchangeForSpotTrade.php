<?php

namespace App\Jobs;

use App\Enums\RefExchangeSellStatusEnum;
use App\Enums\SpotStatusEnum;
use App\Models\Market;
use App\Models\SpotTrade;
use App\Repositories\Interfaces\ExchangeRepositoryInterface;
use App\Services\Exchanges\AdminNotification;
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
 * Market-buy the matched quantity on the reference exchange when a user
 * bought that quantity from the bot.
 *
 * If the exchange has no USDT, the trade stays PENDING so an admin can
 * press the manual buy button, and the admins are notified.
 */
class BuyOnRefExchangeForSpotTrade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly int $spotTradeId,
        private readonly int $marketId,
        private readonly string $quantity
    ) {}

    public function handle(ExchangeService $exchangeService, ExchangeRepositoryInterface $exchangeRepository): void
    {
        try {
            $spotTrade = SpotTrade::find($this->spotTradeId);

            if (!$spotTrade) {
                Log::channel('spot-ref-exchange')->warning("SpotTrade not found for ref exchange buy", [
                    'spot_trade_id' => $this->spotTradeId,
                ]);
                return;
            }

            Log::channel('spot-ref-exchange')->info("Starting ref exchange buy for spot trade", [
                'spot_trade_id' => $this->spotTradeId,
                'market_id' => $this->marketId,
                'quantity' => $this->quantity,
            ]);

            $response = $exchangeService->buyForSpot(
                resolve(SpotRefExchangeSellRequestDTO::class)
                    ->setSpotTradeId($this->spotTradeId)
                    ->setMarketId($this->marketId)
                    ->setQuantity($this->quantity)
            );

            if ($response->isDone()) {
                $spotTrade->update(['ref_exchange_sell_status' => RefExchangeSellStatusEnum::COMPLETED]);

                Log::channel('spot-ref-exchange')->info("Ref exchange buy completed successfully for spot trade", [
                    'spot_trade_id' => $this->spotTradeId,
                    'market_id' => $this->marketId,
                    'quantity' => $this->quantity,
                ]);

                return;
            }

            if ($response->getSpotStatus() === SpotStatusEnum::NotEnoughBalance) {
                $exchange = $exchangeRepository->getActiveExchange();
                $market = Market::find($this->marketId);

                AdminNotification::sendRefExchangeNotEnoughBalance(
                    $exchange?->name ?? 'صرافی مرجع',
                    $market?->base_currency ?? '',
                    $this->quantity,
                    'buy',
                    '/spot/trades'
                );
            }

            // Keep PENDING so admin can trigger the buy manually.
            Log::channel('spot-ref-exchange')->error("Ref exchange buy failed for spot trade", [
                'spot_trade_id' => $this->spotTradeId,
                'market_id' => $this->marketId,
                'quantity' => $this->quantity,
                'error_message' => $response->getErrorMessage(),
                'error_code' => $response->getErrorCode(),
            ]);
        } catch (Throwable $e) {
            Log::channel('spot-ref-exchange')->error("Exception during ref exchange buy for spot trade", [
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
        $spotTrade = SpotTrade::find($this->spotTradeId);
        $spotTrade?->update(['ref_exchange_sell_status' => RefExchangeSellStatusEnum::FAILED]);

        Log::channel('spot-ref-exchange')->critical("Job failed permanently for spot trade ref exchange buy", [
            'spot_trade_id' => $this->spotTradeId,
            'market_id' => $this->marketId,
            'quantity' => $this->quantity,
            'exception' => $exception->getMessage(),
        ]);
    }
}
