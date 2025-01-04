<?php

namespace App\Http\Controllers\Exchange;

use App\Enums\CurrencyChainEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\CreateCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Models\CurrencyChain;
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

        $currencies = Currency::query()->with('chains')->filterBy(request()->all())->get();
        return view('dashboard.exchange.currency.index', ['currencies' => $currencies]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currencies_type = CurrencyChainEnum::cases();
        return view('dashboard.exchange.currency.create',
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
            'symbol' => $request->symbol,
            'is_active' => $request->is_active
        ]);
        if ($request->hasFile('logo')) {
            $timestamp = now()->timestamp;
            $imageName = $currency->id . '_' . $currency->symbol .'_'.$timestamp. '.' . $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->storeAs('coins', $imageName, ['disk' => 'public']);
            $currency->update(['logo' => $imageName]);
        }
        Toast::message('ارز مورد نظر با موفقیت ایجاد شد.')->success()->notify();
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
    public function edit(Currency $currency)
    {

        $currencies_type = CurrencyChainEnum::cases();

        $currency->load('chains');
        return view('dashboard.exchange.currency.edit',
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
            $imageName = $currency->id . '_' . $currency->symbol .'_'.$timestamp .'.' . $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->storeAs('coins', $imageName, ['disk' => 'public']);
            $currency->update(['logo' => $imageName]);
        }
        $currency->update([
            'name' => $request->name,
            'symbol' => $request->symbol,
            'is_active' => $request->is_active
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
