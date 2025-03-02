<?php

namespace App\Services\Spot;

class OrderMatchingEngine
{
    public function processOrder(Order $order)
    {
        $oppositeType = $order->type === 'buy' ? 'sell' : 'buy';

        $query = Order::where('type', $oppositeType)
            ->where('status', 'open')
            ->lockForUpdate();

        if ($order->type === 'buy') {
            $query->where('price', '<=', $order->price)
                ->orderBy('price')->orderBy('created_at');
        } else {
            $query->where('price', '>=', $order->price)
                ->orderByDesc('price')->orderBy('created_at');
        }

        $matchingOrders = $query->get();
        $remaining = $order->remaining_quantity;

        foreach ($matchingOrders as $match) {
            if ($remaining <= 0) {
                break;
            }

            $fillQty = min($remaining, $match->remaining_quantity);

            // Update matched order
            $match->remaining_quantity -= $fillQty;
            $match->status = $match->remaining_quantity > 0 ? 'open' : 'filled';
            $match->save();

            // Update current order
            $remaining -= $fillQty;
        }

        // Update original order
        $order->remaining_quantity = $remaining;
        $order->status = $remaining > 0 ? 'open' : 'filled';
        $order->save();
    }
}
