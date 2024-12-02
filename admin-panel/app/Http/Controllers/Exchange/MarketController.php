<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Market;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index()
    {
        $markets = Market::with(['baseCurrency', 'quoteCurrency'])->get();
        return view('dashboard.exchange.market.index',[
            'markets'=>$markets
        ]);
    }
}
