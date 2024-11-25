<?php

namespace App\Http\Controllers\Exchange;

use App\Enums\CurrenciesTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currencies = Currency::query()->filterBy(request()->all())->get();
        return view('dashboard.exchange.currency.index',['currencies' => $currencies]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currencies_type = CurrenciesTypeEnum::cases();
        return view('dashboard.exchange.currency.create',
            ['currencies_type' => $currencies_type]
        );
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
