<?php

namespace App\Http\Controllers\Exchange;


use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Market\BulkUpdateExchangeRequest;
use App\Http\Requests\Market\StoreMarketRequest;
use App\Http\Requests\Market\UpdateMarketRequest;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\ExchangePrice;
use App\Models\Market;
use App\Services\Exchanges\WithdrawalFee\ExchangeFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    public function index()
    {
        $activeExchange = Exchange::query()->active()->first();
        $exchanges = Exchange::query()->orderBy('priority')->get();
        $markets = Market::with(['baseCurrency', 'quoteCurrency', 'activeExchangePrice.exchange'])->get();

        return view('dashboard.exchange.market.index', [
            'markets' => $markets,
            'activeExchange' => $activeExchange,
            'exchanges' => $exchanges,
        ]);
    }

    public function create()
    {
        $exchanges = Exchange::query()->orderBy('priority')->get();
        $currencies = Currency::all();
        return view('dashboard.exchange.market.create', ['currencies' => $currencies, 'exchanges' => $exchanges]);
    }

    public function store(StoreMarketRequest $request)
    {
        // Get the base currency from the selected currency ID
        $baseCurrency = Currency::findOrFail($request->symbol);

        // Get the quote currency (USDT is hardcoded in the form)
        $quoteCurrency = Currency::where('symbol', 'USDT')->firstOrFail();

        // Check if a market with the same currency pair already exists
        $existingMarket = Market::where('base_currency', $baseCurrency->symbol)
            ->where('quote_currency', $quoteCurrency->symbol)
            ->first();

        if ($existingMarket) {
            return redirect()
                ->route('admin.market.create')
                ->withInput()
                ->withErrors(['symbol' => 'بازار با جفت ارز ' . $baseCurrency->symbol . '-' . $quoteCurrency->symbol . ' قبلاً ایجاد شده است.']);
        }

        // Create the market
        $market = Market::create([
            'base_currency' => $baseCurrency->symbol,
            'quote_currency' => $quoteCurrency->symbol,
            'min_otc_amount' => $request->min_otc_amount,
            'max_otc_amount' => $request->max_otc_amount,
            'min_trade_amount' => $request->min_trade_amount,
            'max_trade_amount' => $request->max_trade_amount,
            'is_active' => $request->has('is_active') ? $request->is_active : false,
            'price_update_enabled' => $request->has('price_update_enabled') ? $request->price_update_enabled : false,
            'ref_exchange_sell_enabled' => $request->has('ref_exchange_sell_enabled') ? $request->ref_exchange_sell_enabled : false,
        ]);

        // Create the exchange price record
        ExchangePrice::create([
            'market_id' => $market->id,
            'exchange_id' => $request->exchange_id,
            'exchange_profit_sell' => $request->exchange_profit_sell,
            'exchange_profit_buy' => $request->exchange_profit_buy,
            'price' => 0, // Initial price will be updated by the price fetching service
            'open_price' => 0, // Initial open price
            'price_update_enabled' => $request->has('price_update_enabled') ? $request->price_update_enabled : false,
        ]);

        // Redirect back with a success message
        return redirect()
            ->route('admin.market.index')
            ->with('success', 'بازار جدید با موفقیت ایجاد شد.');
    }

    public function edit(Market $market)
    {
        $market->load(['baseCurrency', 'quoteCurrency', 'activeExchangePrice']);
        $exchanges = Exchange::query()->orderBy('priority')->get();

        // Fetch 7-day market history data
        $marketHistory = \App\Models\MarketHistory::where('market_id', $market->id)
            ->where('timestamp', '>=', now()->subDays(7))
            ->orderBy('timestamp', 'asc')
            ->get(['close', 'timestamp']);

        return view('dashboard.exchange.market.edit', [
            'market' => $market,
            'exchanges' => $exchanges,
            'marketHistory' => $marketHistory
        ]);
    }

    public function update(UpdateMarketRequest $request, Market $market)
    {

        // Update the market with the validated data
        $market->update([
            'min_otc_amount' => $request->min_otc_amount,
            'max_otc_amount' => $request->max_otc_amount,
            'min_trade_amount' => $request->min_trade_amount,
            'max_trade_amount' => $request->max_trade_amount,
            'is_active' => $request->has('is_active') ? $request->is_active : false,
            'price_update_enabled' => $request->has('price_update_enabled') ? $request->price_update_enabled : false,
            'ref_exchange_sell_enabled' => $request->has('ref_exchange_sell_enabled') ? $request->ref_exchange_sell_enabled : false,
        ]);
        $market->activeExchangePrice->exchange_profit_sell = $request->exchange_profit_sell;
        $market->activeExchangePrice->exchange_profit_buy = $request->exchange_profit_buy;
        $market->activeExchangePrice->exchange_id = $request->exchange_id;
        $market->activeExchangePrice->save();
        // Redirect back with a success message
        return redirect()
            ->route('admin.market.index')
            ->with('success', 'اطلاعات بازار با موفقیت به‌روز شد.');
    }

    public function toggleHome(Market $market): JsonResponse
    {
        $market->show_in_home = !$market->show_in_home;
        $market->save();

        return response()->json(['show_in_home' => $market->show_in_home]);
    }

    public function bulkUpdateExchange(BulkUpdateExchangeRequest $request): JsonResponse
    {
        $exchange = Exchange::query()->findOrFail($request->exchange_id);

        $updated = ExchangePrice::query()->update(['exchange_id' => $exchange->id]);

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'exchange' => [
                'id' => $exchange->id,
                'name' => $exchange->name,
            ],
            'message' => "صرافی مرجع همه بازارها ({$updated} مورد) به {$exchange->name} تغییر کرد.",
        ]);
    }

    /**
     * Get CoinEx min OTC amount for a specific market
     */
    public function getCoinexMinOtcAmount(Market $market): JsonResponse
    {
        try {
            $service = ExchangeFactory::make('coinex');
            $fetchMarkets = collect($service->fetchMinTrade())->keyBy('base_ccy');

            $minAmount = $fetchMarkets[$market->base_currency]['min_amount'] ?? null;

            if ($minAmount === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'حداقل مقدار معامله برای این بازار در CoinEx یافت نشد'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'min_amount' => $minAmount,
                'formatted_amount' => formatNumberTrimZeros($minAmount)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات از CoinEx: ' . $e->getMessage()
            ], 500);
        }
    }
}
