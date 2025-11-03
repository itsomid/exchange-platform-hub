<?php

namespace App\Services\SpotBot;

use App\Services\SpotBot\DTO\InMemoryBotOrderDTO;
use App\Models\SpotOrder;
use App\Models\LockedBalanceDetail;
use App\Models\Market;
use App\Enums\SpotOrderSourceEnum;
use App\Enums\LockedBalanceTypeEnum;
use App\Enums\SpotOrderSideEnum;
use App\Helpers\Math;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Service to persist in-memory bot orders to database when they are matched
 */
class InMemoryOrderPersistenceService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository
    ) {}

    /**
     * Convert an in-memory bot order to database record
     * Only called when the order has been matched/filled
     * 
     * @param InMemoryBotOrderDTO $inMemoryOrder
     * @return SpotOrder
     */
    public function persistOrder(InMemoryBotOrderDTO $inMemoryOrder): SpotOrder
    {
        $spotOrder = new SpotOrder();
        $spotOrder->user_id = $inMemoryOrder->user_id;
        $spotOrder->market_id = $inMemoryOrder->market_id;
        $spotOrder->side = $inMemoryOrder->side;
        $spotOrder->type = $inMemoryOrder->type;
        $spotOrder->status = $inMemoryOrder->status;
        $spotOrder->price = $inMemoryOrder->price;
        $spotOrder->quantity = $inMemoryOrder->quantity;
        $spotOrder->filled_quantity = $inMemoryOrder->filled_quantity;
        $spotOrder->source = SpotOrderSourceEnum::BOT;
        $spotOrder->created_at = \Carbon\Carbon::createFromTimestamp($inMemoryOrder->created_at, config('app.timezone'));
        $spotOrder->updated_at = \Carbon\Carbon::createFromTimestamp($inMemoryOrder->updated_at, config('app.timezone'));
        $spotOrder->save();

        // Create LockedBalanceDetail for bot orders when persisted to DB
        // This is critical to track locked balance properly, especially for partial fills and cancellations
        $market = Market::find($inMemoryOrder->market_id);
        if ($market) {
            if ($inMemoryOrder->side === SpotOrderSideEnum::BUY) {
                // For buy orders, locked amount is quantity * price in quote currency
                $lockedAmount = Math::mul($inMemoryOrder->quantity, $inMemoryOrder->price);
                $currency = $market->quote_currency;
            } else {
                // For sell orders, locked amount is quantity in base currency
                $lockedAmount = $inMemoryOrder->quantity;
                $currency = $market->base_currency;
            }

            // Get wallet to create LockedBalanceDetail
            $wallet = $this->walletRepository->getOrCreateWallet($inMemoryOrder->user_id, $currency);
            
            LockedBalanceDetail::query()->create([
                'wallet_id' => $wallet->id,
                'amount' => $lockedAmount,
                'type' => LockedBalanceTypeEnum::SPOT,
                'spot_order_id' => $spotOrder->id,
                'description' => "سفارش ربات #{$spotOrder->id} - مقدار قفل شده: {$lockedAmount} {$currency}",
            ]);

            Log::channel('spot-bot')->info('LockedBalanceDetail created for persisted bot order', [
                'order_id' => $spotOrder->id,
                'locked_amount' => $lockedAmount,
                'currency' => $currency,
            ]);
        }

        Log::channel('spot-bot')->info('In-memory bot order persisted to database', [
            'in_memory_order_id' => $inMemoryOrder->id,
            'database_order_id' => $spotOrder->id,
            'user_id' => $inMemoryOrder->user_id,
            'market_id' => $inMemoryOrder->market_id,
            'filled_quantity' => $inMemoryOrder->filled_quantity,
        ]);

        return $spotOrder;
    }

    /**
     * Update an existing database order with new filled quantity
     * 
     * @param SpotOrder $spotOrder
     * @param string $filledQuantity
     * @return SpotOrder
     */
    public function updateFilledQuantity(SpotOrder $spotOrder, string $filledQuantity): SpotOrder
    {
        $spotOrder->filled_quantity = $filledQuantity;
        $spotOrder->save();

        return $spotOrder;
    }
}

