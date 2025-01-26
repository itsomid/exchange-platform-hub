<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\ExchangeAssetsWithdrawal;

class ExchangeAssetsWithdrawalController extends Controller
{
    public function index()
    {
        $withdraws = ExchangeAssetsWithdrawal::all();

        return view('dashboard.exchange.wallet.exchange-assets-withdrawal',[
            'withdraws' => $withdraws
        ]);
    }


    public function create()
    {

    }

    public function store()
    {

    }

}
