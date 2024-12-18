<?php

namespace App\Http\Controllers\Exchange;


use App\Http\Controllers\Controller;
use App\Http\Requests\Market\UpdateMarketRequest;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\Market;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index()
    {
        $activeExchange = Exchange::query()->active()->first();
        $markets = Market::with(['baseCurrency', 'quoteCurrency', 'activeExchangePrice.exchange'])->get();
//        return $markets[0]->activeExchangePrices->price;
        return view('dashboard.exchange.market.index', [
            'markets' => $markets,
            'activeExchange' => $activeExchange
        ]);
    }

    public function create()
    {
        $exchanges = Exchange::query()->orderBy('priority')->get();
        $currencies = Currency::all();
        return view('dashboard.exchange.market.create', ['currencies' => $currencies, 'exchanges' => $exchanges]);
    }

    public function edit(Market $market)
    {
        $market->load(['baseCurrency', 'quoteCurrency', 'activeExchangePrice']);
        $exchanges = Exchange::query()->orderBy('priority')->get();
        return view('dashboard.exchange.market.edit', ['market' => $market, 'exchanges' => $exchanges]);
    }

    public function update(UpdateMarketRequest $request, Market $market)
    {

        // Update the market with the validated data
        $market->update([
            'min_trade_amount' => $request->min_trade_amount,
            'max_trade_amount' => $request->max_trade_amount,
            'is_active' => $request->has('is_active') ? $request->is_active : false,
        ]);
        $market->activeExchangePrice->exchange_profit = $request->exchange_profit;
        $market->activeExchangePrice->exchange_id = $request->exchange_id;
        $market->activeExchangePrice->save();
        // Redirect back with a success message
        return redirect()
            ->route('admin.market.index')
            ->with('success', 'اطلاعات بازار با موفقیت به‌روز شد.');
    }
}
