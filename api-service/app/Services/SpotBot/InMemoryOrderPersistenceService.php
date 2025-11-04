<?php

namespace App\Services\SpotBot;

use App\Services\SpotBot\DTO\InMemoryBotOrderDTO;
use App\Models\SpotOrder;
use App\Enums\SpotOrderSourceEnum;
use Illuminate\Support\Facades\Log;

/**
 * Service to persist in-memory bot orders to database when they are matched
 */
class InMemoryOrderPersistenceService
{
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

