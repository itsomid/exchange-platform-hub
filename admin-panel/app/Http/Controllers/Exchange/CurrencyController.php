<?php

namespace App\Http\Controllers\Exchange;

use App\Enums\CurrencyChainEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\CreateCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CurrencyController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $availableChains = CurrencyChain::query()->distinct()->orderBy('chain')->pluck('chain');

        $currencies = Currency::query()->with('chains')->filterBy(request()->all())->get();

        $currenciesWithChainsCount = $currencies->filter(function ($currency) {
            return $currency->chains->isNotEmpty();
        })->count();
        $currenciesWithoutChainsCount = $currencies->filter(function ($currency) {
            return $currency->chains->isEmpty();
        })->count();

        return view('dashboard.exchange.currency.index', [
            'currencies' => $currencies,
            'currenciesWithChainsCount' => $currenciesWithChainsCount,
            'currenciesWithoutChainsCount' => $currenciesWithoutChainsCount,
            'availableChains' => $availableChains,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currencies_type = CurrencyChainEnum::cases();
        return view(
            'dashboard.exchange.currency.create',
            ['currencies_type' => $currencies_type]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCurrencyRequest $request)
    {
        $currency = Currency::create([
            'name' => $request->name,
            'persian_name' => $request->persian_name,
            'symbol' => $request->symbol,
            'is_active' => isset($request->is_active) && $request->is_active == '1',
        ]);

        if ($request->hasFile('logo')) {
            $timestamp = now()->timestamp;
            $imageName = $currency->id . '_' . $currency->symbol . '_' . $timestamp . '.' . $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->storeAs('coins', $imageName, ['disk' => 'public']);
            $currency->update(['logo' => $imageName]);
        }

        // Automatically create exchange wallet for the new currency
        $walletService = app(WalletService::class);
        $exchangeWallet = $walletService->createExchangeWallet($currency->symbol);

        if ($exchangeWallet) {
            Toast::message('ارز مورد نظر با موفقیت ایجاد شد و کیف پول صرافی نیز ساخته شد.')->success()->notify();
        } else {
            Toast::message('ارز مورد نظر ایجاد شد اما در ساخت کیف پول صرافی مشکلی پیش آمد.')->warning()->notify();
        }

        return redirect()->route('admin.currency.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Currency $currency)
    {
        $currency->load(['chains', 'baseMarket.activeExchangePrice']);

        $priceData = null;
        if ($currency->baseMarket && $currency->baseMarket->activeExchangePrice) {
            $ep = $currency->baseMarket->activeExchangePrice;
            $priceData = [
                'market'                   => $currency->baseMarket->name,
                'price'                    => $ep->price,
                'open_price'               => $ep->open_price,
                'price_change_percentage'  => $ep->price_change_percentage,
                'exchange_sell_price'      => $ep->exchange_sell_price,
                'exchange_buy_price'       => $ep->exchange_buy_price,
            ];
        }

        return response()->json([
            'currency'  => $currency,
            'logo_url'  => $currency->coinLogo(),
            'edit_url'  => route('admin.currency.edit', $currency),
            'price'     => $priceData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Currency $currency)
    {

        $currencies_type = CurrencyChainEnum::cases();

        $currency->load('chains');
        return view(
            'dashboard.exchange.currency.edit',
            ['currencies_type' => $currencies_type],
            ['currency' => $currency]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency)
    {


        $oldImage = $currency->logo;
        if ($request->hasFile('logo')) {
            $timestamp = now()->timestamp;
            $imageName = $currency->id . '_' . $currency->symbol . '_' . $timestamp . '.' . $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->storeAs('coins', $imageName, ['disk' => 'public']);
            $currency->update(['logo' => $imageName]);
        }
        $currency->update([
            'name' => $request->name,
            'persian_name' => $request->persian_name,
            'symbol' => $request->symbol,
            'price_precision' => $request->price_precision,
            'amount_precision' => $request->amount_precision,
            'inter_transfer_enabled' => isset($request->inter_transfer_enabled) && $request->inter_transfer_enabled == '1',
            'max_auto_withdraw_amount' => $request->max_auto_withdraw_amount,
            'is_active' => isset($request->is_active) && $request->is_active == '1',
        ]);
        // Check if the old image exists and delete it
        if ($request->hasFile('logo')) {
            $oldImagePath = 'coins/' . $oldImage;
            if ($oldImage && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
        }
        Toast::message('ارز مورد نظر با موفقیت ایجاد شد.')->success()->notify();
        return redirect()->route('admin.currency.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
