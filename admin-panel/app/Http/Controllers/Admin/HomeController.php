<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Transaction;

class HomeController extends Controller
{
    public function index()
    {
        $OTCFeeTransactionsByCurrency = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::OTC)
            ->selectRaw('wallet_id, SUM(amount) as total_amount')
            ->groupBy('wallet_id')
            ->with('wallet.currency')
            ->get();

        $withdrawalFeeTransactionsByCurrency = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::WITHDRAWAL_FEE)
            ->selectRaw('wallet_id, SUM(amount) as total_amount')
            ->groupBy('wallet_id')
            ->with('wallet.currency')
            ->get();


        return view('dashboard.home.index', [
            'OTCFeeTransactionsByCurrency' => $OTCFeeTransactionsByCurrency,
            'withdrawalFeeTransactionsByCurrency' => $withdrawalFeeTransactionsByCurrency
        ]);
    }
}
