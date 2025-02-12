<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\ExchangeTransaction;

class RefExchangeController extends Controller
{
    public function index()
    {
        $exchanges = Exchange::all();
        $activeExchange = Exchange::active()->first();

        return view('dashboard.exchange.ref_exchange.index', [
            'exchanges' => $exchanges,
            'activeExchange' => $activeExchange
        ]);
    }

    public function boughtHistory()
    {
        $boughtHistoryByCurrency = ExchangeTransaction::all()->groupBy('currency')->map(function ($items) {
            return [
                'currency' => $items->first()['currency'],
                'total_amount' => $items->sum(fn ($item) => (float) $item['amount']),
                'total_fee' => $items->sum(fn ($item) => (float) $item['fee']),
            ];
        });
         $transaction = ExchangeTransaction::orderBy('created_at','desc')->get();
        return view('dashboard.exchange.ref_exchange.bought-history',
            ['transactions' => $transaction]
        );

    }
}
