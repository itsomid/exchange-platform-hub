<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Http\Requests\Market\UpdateMarketRequest;
use App\Models\Currency;
use App\Models\Market;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index()
    {
        $markets = Market::with(['baseCurrency', 'quoteCurrency'])->get();
        return view('dashboard.exchange.market.index',[
            'markets'=>$markets
        ]);
    }

    public function create()
    {

        $currencies = Currency::all();
        return view('dashboard.exchange.market.create',['currencies'=>$currencies]);
    }
    public function edit(Market $market)
    {
         $market->load(['baseCurrency', 'quoteCurrency']);
        return view('dashboard.exchange.market.edit',['market'=>$market]);
    }

    public function update(UpdateMarketRequest $request, Market $market)
    {

        // Update the market with the validated data
        $market->update([
            'min_trade_amount' => $request->min_trade_amount,
            'max_trade_amount' => $request->max_trade_amount,
            'exchange_profit'   => $request->exchange_profit,
            'is_active'        => $request->has('is_active') ? $request->is_active : false,
        ]);

        // Redirect back with a success message
        return redirect()
            ->route('admin.market.index')
            ->with('success', 'اطلاعات بازار با موفقیت به‌روز شد.');
    }
}
