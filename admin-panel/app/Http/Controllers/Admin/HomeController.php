<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\User;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class HomeController extends Controller
{
    public function index()
    {

        $today = now()->toDateString(); // Get today's date

           $OTCFeeTransactionsByCurrency = Transaction::where('type', TransactionTypeEnum::FEE)
             ->where('subtype', TransactionSubTypeEnum::OTC)
             ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
             ->selectRaw('wallets.currency_symbol, SUM(transactions.amount) as total_amount, COUNT(transactions.id) as transaction_count')
             ->groupBy('wallets.currency_symbol')
             ->get();

        $withdrawalFeeTransactionsByCurrency = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::WITHDRAWAL_FEE)
            ->selectRaw('wallet_id, SUM(amount) as total_amount')
            ->groupBy('wallet_id')
            ->with('wallet.currency')
            ->get();

        $withdrawalSums = Withdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount')
            ->where('status', WithdrawalStatusEnum::COMPLETED)
            ->groupBy('currency_symbol')
            ->get();

        $totalDepositsValue = Deposit::with('currency')
            ->whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek(Carbon::FRIDAY)])
            ->get()
            ->sum(function ($deposit) {
                return $deposit->amount * $deposit->currency->exchange_price;
            });

        $totalWithdrawalValue = Withdrawal::with('currency')
            ->whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek(Carbon::FRIDAY)])
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->amount * $withdraw->currency->exchange_price;
            });


        return view('dashboard.home.index', [
            'OTCFeeTransactionsByCurrency' => $OTCFeeTransactionsByCurrency,
            'withdrawalFeeTransactionsByCurrency' => $withdrawalFeeTransactionsByCurrency,
            'withdrawalSums' => $withdrawalSums,
            'totalDepositsValue' => $totalDepositsValue,
            'totalWithdrawalValue' => $totalWithdrawalValue,
        ]);
    }


}
