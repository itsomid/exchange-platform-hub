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
use App\Services\Exchanges\ExchangeData\ReferenceMarketSupport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    public function index(ReferenceMarketSupport $support)
    {
        $activeExchange = Exchange::query()->active()->first();
        $exchanges = Exchange::query()->orderBy('priority')->get();
        $markets = Market::with(['baseCurrency', 'quoteCurrency', 'activeExchangePrice.exchange'])->get();
        $unsupportedMarkets = $support->unsupported($markets)
            ->keyBy(fn (array $row) => $row['market']->id);

        return view('dashboard.exchange.market.index', [
            'markets' => $markets,
            'activeExchange' => $activeExchange,
            'exchanges' => $exchanges,
            'unsupportedMarkets' => $unsupportedMarkets,
            'referenceSupportFailed' => $support->checkFailed,
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
     * Whether the selected reference exchange lists this market, and its min trade size.
     */
    public function getMinOtcAmount(Request $request, Market $market, ReferenceMarketSupport $support): JsonResponse
    {
        $exchange = $this->referenceExchange($request, $market);
        if (!$exchange) {
            return response()->json([
                'success' => false,
                'supported' => null,
                'message' => 'صرافی مرجعی برای این بازار انتخاب نشده است',
            ], 404);
        }

        $listed = $support->tradingMarkets($exchange->slug);
        $pair = $market->base_currency . '/' . $market->quote_currency;

        if ($listed === null) {
            if ($support->checkFailed) {
                return response()->json([
                    'success' => false,
                    'supported' => null,
                    'exchange' => $exchange->name,
                    'symbol' => $pair,
                    'message' => 'خطا در دریافت اطلاعات از صرافی مرجع',
                ], 500);
            }

            return response()->json([
                'success' => false,
                'supported' => null,
                'exchange' => $exchange->name,
                'symbol' => $pair,
                'message' => 'بررسی پشتیبانی برای این صرافی در دسترس نیست',
            ]);
        }

        $row = $listed[strtoupper($market->base_currency . $market->quote_currency)] ?? null;
        if ($row === null) {
            return response()->json([
                'success' => false,
                'supported' => false,
                'exchange' => $exchange->name,
                'symbol' => $pair,
                'message' => "بازار {$pair} روی صرافی مرجع {$exchange->name} پشتیبانی نمی‌شود.",
            ]);
        }

        return response()->json([
            'success' => true,
            'supported' => true,
            'exchange' => $exchange->name,
            'symbol' => $pair,
            'min_amount' => $row['min_amount'],
            'formatted_amount' => formatNumberTrimZeros($row['min_amount']),
            'formatted_min_qty' => isset($row['min_qty']) ? formatNumberTrimZeros($row['min_qty']) : null,
            'formatted_min_notional' => isset($row['min_notional']) ? formatNumberTrimZeros($row['min_notional']) : null,
            'formatted_price' => isset($row['price']) ? formatNumberTrimZeros($row['price']) : null,
            'min_notional_margin_percent' => $row['min_notional_margin_percent'] ?? null,
            'limited_by' => $row['limited_by'] ?? null,
        ]);
    }

    private function referenceExchange(Request $request, Market $market): ?Exchange
    {
        if ($request->filled('exchange_id')) {
            return Exchange::query()->find($request->integer('exchange_id'));
        }

        $market->loadMissing('activeExchangePrice.exchange');

        return $market->activeExchangePrice?->exchange
            ?? Exchange::query()->active()->first();
    }
}
