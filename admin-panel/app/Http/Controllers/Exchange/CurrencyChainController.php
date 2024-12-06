<?php

namespace App\Http\Controllers\Exchange;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use Illuminate\Http\Request;

class CurrencyChainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getChains(Currency $currency)
    {
        $currency->load('chains');

        return view('dashboard.exchange.currency.edit-chains', ['currency' => $currency]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function createChain(Currency $currency)
    {
        return view('dashboard.exchange.currency.create-chain', ['currency' => $currency]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeChain(Request $request, Currency $currency)
    {

        // Validate the incoming data
        $validated = $request->validate([
            'chain' => 'required|string',  // Ensure the chain is selected
            'min_deposit_amount' => 'required|numeric',
            'min_withdraw_amount' => 'required|numeric',
            'deposit_delay_minutes' => 'required|integer',
            'safe_confirmations' => 'required|integer',
            'exchange_profit' => 'required|numeric',
            'network_fee' => 'required|numeric',
            'deposit_enabled' => 'nullable|boolean',
            'withdraw_enabled' => 'nullable|boolean',
        ]);

        // Check if the chain already exists for the given currency
        $existingChain = CurrencyChain::where('currency_id', $currency->id)
            ->where('chain', $request->chain)  // Check if the selected chain exists for this currency
            ->first();

        // If the chain exists, return a message
        if ($existingChain) {
            Toast::message(" شبکه{$request->chain} روی این کوین موجود است. ")->danger()->notify();
            return redirect()->back();
        }

        // Otherwise, create a new chain for the currency
        $currencyChain = CurrencyChain::query()->create([
            'currency_id' => $currency->id,
            'chain' => $request->chain,
            'min_deposit_amount' => $request->min_deposit_amount,
            'min_withdraw_amount' => $request->min_withdraw_amount,
            'deposit_delay_minutes' => $request->deposit_delay_minutes,
            'safe_confirmations' => $request->safe_confirmations,
            'exchange_profit' => $request->exchange_profit,
            'network_fee' => $request->network_fee,
            'deposit_enabled' => $request->has('deposit_enabled'),  // Convert checkbox to boolean
            'withdraw_enabled' => $request->has('withdraw_enabled'),  // Convert checkbox to boolean
        ]);


        Toast::message(" با موفقیت ایجاد شد.'{$currency->name}' شبکه بر بستر کوین ")->success()->notify();
        // Redirect back with a success message
        return redirect()->route('admin.currency.index');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateChains(Request $request, Currency $currency)
    {

        $validated = $request->validate([
            'chains.*.min_deposit_amount' => 'required|numeric',
            'chains.*.min_withdraw_amount' => 'required|numeric',
            'chains.*.deposit_delay_minutes' => 'required|integer',
            'chains.*.safe_confirmations' => 'required|integer',
            'chains.*.exchange_profit' => 'required|numeric',
            'chains.*.network_fee' => 'required|numeric',
            'chains.*.deposit_enabled' => 'nullable|boolean',
            'chains.*.withdraw_enabled' => 'nullable|boolean',
        ]);

        // Loop through each chain and update the values
        foreach ($currency->chains as $chain) {
            // Check if the chain is part of the request
            if (isset($request->chains[$chain->id])) {
                $chainData = $request->chains[$chain->id];

                // Update chain attributes
                $chain->update([
                    'min_deposit_amount' => $chainData['min_deposit_amount'],
                    'min_withdraw_amount' => $chainData['min_withdraw_amount'],
                    'deposit_delay_minutes' => $chainData['deposit_delay_minutes'],
                    'safe_confirmations' => $chainData['safe_confirmations'],
                    'exchange_profit' => $chainData['exchange_profit'],
                    'network_fee' => $chainData['network_fee'],
                    'deposit_enabled' => isset($chainData['deposit_enabled']) && $chainData['deposit_enabled'] == '1',
                    'withdraw_enabled' => isset($chainData['withdraw_enabled']) && $chainData['withdraw_enabled'] == '1',
                ]);
            }
        }

        // Redirect back with a success message
        Toast::message('کانفیگ های شبکه با موفقیت بروز رسانی شد.')->success()->notify();
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
