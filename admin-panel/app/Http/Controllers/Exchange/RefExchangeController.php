<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\ExchangeTransaction;
use Illuminate\Http\Request;

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

    public function edit($id)
    {
        $exchange = Exchange::find($id);
        return view('dashboard.exchange.ref_exchange.edit', ['exchange' => $exchange]);
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

    public function update(Request $request, $id)
    {
        $exchange = Exchange::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:exchanges,slug,' . $exchange->id,
            'priority' => 'required|integer',
        ]);
        $exchange->name = $validated['name'];
        $exchange->slug = $validated['slug'];
        $exchange->priority = $validated['priority'];
        if ($request->has('is_active')) {
            Exchange::setActiveExchange($exchange);
        } else {
            $exchange->is_active = false;
            $exchange->save();
        }
        return redirect()->route('admin.exchange.index')->with('success', 'صرافی با موفقیت ویرایش شد.');
    }
}
