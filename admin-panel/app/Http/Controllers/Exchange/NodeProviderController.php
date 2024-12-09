<?php

namespace App\Http\Controllers\Exchange;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\NodeProvider;
use App\Rules\UniquePriorityForCurrency;
use Illuminate\Http\Request;

class NodeProviderController extends Controller
{
    public function edit(Currency $currency)
    {
        $currency->load('NodeProviders');
        return view('dashboard.exchange.currency.edit-node-providers', [
            'currency' => $currency
        ]);
    }

    public function update(Request $request, Currency $currency)
    {


        $validated = $request->validate([
            'nodes.*.name' => 'required|string',
            'nodes.*.base_url' => 'required|url',
            'nodes.*.api_key' => 'nullable|string',
            'nodes.*.is_active' => 'nullable|boolean',
            'nodes.*.priority' => [
                'required',
                'integer',
                new UniquePriorityForCurrency($currency->id)
            ],

        ]);

//        return $request;
        foreach ($currency->nodeproviders as $node) {

            if (isset($request->nodes[$node->id])) {

                $nodeData = $request->nodes[$node->id];
//                return $request;
                $node->update([
                    'name' => $nodeData['name'],
                    'base_url' => $nodeData['base_url'],
                    'api_key' => $nodeData['api_key'],
                    'priority' => $nodeData['priority'],
                    'is_active' => isset($nodeData['is_active']) && $nodeData['is_active'] == '1'
                ]);
            }
        }

        // Redirect back with a success message
        Toast::message('کانفیگ های node با موفقیت بروز رسانی شد.')->success()->notify();
        return redirect()->route('admin.currency.index');
    }
}
