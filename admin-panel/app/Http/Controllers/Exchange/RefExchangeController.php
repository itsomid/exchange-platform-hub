<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\ExchangeAssetsWithdrawal;
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
        $boughtHistoryByCurrency = ExchangeTransaction::selectRaw('market, SUM(amount) as total_amount, SUM(fee) as total_fee')
            ->groupBy('market')
            ->get();

        $totalExchangeBoughtFee = ExchangeTransaction::sum('fee');
        $totalExchangeBoughtValue = ExchangeTransaction::all()->sum(function ($transaction) {
            return (float) data_get($transaction, 'response.data.filled_value', 0);
        });


        $transaction = ExchangeTransaction::with(['exchangeMarket','currency'])->orderBy('created_at', 'desc')->get();


        return view('dashboard.exchange.ref_exchange.bought-history', [
            'transactions' => $transaction,
            'boughtHistoryByCurrency' => $boughtHistoryByCurrency,
            'totalExchangeBoughtFee' => $totalExchangeBoughtFee,
            'totalExchangeBoughtValue' => $totalExchangeBoughtValue,
        ]);

    }
}
