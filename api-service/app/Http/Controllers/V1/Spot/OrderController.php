<?php

namespace App\Http\Controllers\V1\Spot;

use App\Http\Controllers\Controller;
use App\Services\Spot\OrderMatchingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:buy,sell',
            'price' => 'required|numeric|min:0.01',
            'quantity' => 'required|numeric|min:0.00000001',
        ]);

        $order = new Order($validated);
        $order->remaining_quantity = $validated['quantity'];

        DB::transaction(function () use ($order) {
            $order->save();
            app(OrderMatchingEngine::class)->processOrder($order);
        });

        return response()->json($order);
    }
}
