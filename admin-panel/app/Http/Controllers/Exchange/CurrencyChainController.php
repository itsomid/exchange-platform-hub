<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyChainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getChains(Currency $currency)
    {
        $currency->load('chains');

        return view('dashboard.exchange.currency.edit-chains',['currency' => $currency]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        return $request;
        $validated = $request->validate([
            'min_deposit_amount' => 'required|numeric',
            'min_withdraw_amount' => 'required|numeric',
            'deposit_delay_minutes' => 'required|integer',
            'safe_confirmations' => 'required|integer',
            'exchange_fee' => 'required|numeric',
            'network_fee' => 'required|numeric',
            'is_internal_transfer_active' => 'nullable|boolean',  // For the internal transfer checkbox
        ]);

        // Handle internal transfer toggle
        $currency->is_internal_transfer_active = $request->has('is_internal_transfer_active');
        $currency->save();  // Save the currency internal transfer status

        // Loop through each chain and update its values
        foreach ($currency->chains as $chain) {
            // For each chain, update the values from the form
            $chainData = $request->except('_token', 'is_internal_transfer_active');  // Exclude CSRF and internal transfer field

            // Each chain will have an `id` in the form input, so we update them individually
            foreach ($chainData as $key => $value) {
                // Handle fields for each chain using the correct field names from the form
                $chain->update([
                    $key => $value,
                ]);
            }
        }

        // Redirect back to the currency page with a success message
        return redirect()->route('admin.currency.index')  // Adjust to your desired redirect
        ->with('success', 'Currency and chains updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
