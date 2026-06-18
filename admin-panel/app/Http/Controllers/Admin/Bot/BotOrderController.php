<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BotOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = BotOrder::with('user')->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('batch_uuid', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%"));
            });
        }

        $orders  = $query->paginate(20)->withQueryString();
        $statuses = ['PENDING', 'PARTIALLY_FILLED', 'FILLED', 'CANCELED'];

        return view('dashboard.bot.orders.index', compact('orders', 'statuses'));
    }

    public function show(BotOrder $botOrder): View
    {
        $botOrder->load([
            'user',
            'buyExecutions.currency',
            'buyExecutions.sellOrders.settlement',
        ]);

        $settlements = \App\Models\Bot\BotTradeSettlement::query()
            ->whereIn('bot_buy_execution_id', $botOrder->buyExecutions->pluck('id'))
            ->with(['sellOrder', 'buyExecution.currency'])
            ->orderBy('settled_at')
            ->get();

        $symbols = $botOrder->buyExecutions->pluck('currency.symbol')->filter()->unique()->values()->all();
        $markets = \App\Models\Market::whereIn('base_currency', $symbols)
            ->where('quote_currency', 'USDT')
            ->get(['id', 'base_currency'])
            ->keyBy('base_currency');

        return view('dashboard.bot.orders.show', compact('botOrder', 'settlements', 'markets'));
    }
}
