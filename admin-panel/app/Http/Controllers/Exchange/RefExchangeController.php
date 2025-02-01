<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Exchange;

class RefExchangeController extends Controller
{
    public function index()
    {
        $exchanges = Exchange::all();
        $activeExchange = Exchange::active()->first();

        return view('dashboard.exchange.ref_exchange.index',[
           'exchanges' => $exchanges,
            'activeExchange' => $activeExchange
        ]);
    }
}
