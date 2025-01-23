<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Withdrawal;

class HomeController extends Controller
{
    public function index()
    {

        $today = now()->toDateString(); // Get today's date


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

        $withdrawalSums = Withdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount')
            ->groupBy('currency_symbol')
            ->get();


        return view('dashboard.home.index', [
            'OTCFeeTransactionsByCurrency' => $OTCFeeTransactionsByCurrency,
            'withdrawalFeeTransactionsByCurrency' => $withdrawalFeeTransactionsByCurrency,
            'withdrawalSums' => $withdrawalSums
        ]);
    }
}
