<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Transaction;


class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['user', 'wallet', 'admin', 'wallet.currency', 'deposit', 'withdrawal'])->filterBy(request()->all())->paginate(100);
        $referralTransactionsCount = Transaction::where('type', TransactionTypeEnum::REFERRAL)->count();

        $OTCFeeTransactionsCount = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::OTC)->count();

        $withdrawalFeeTransactionsCount = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::WITHDRAWAL_EXCHANGE_FEE)->count();


         $OTCFeeTransactionsSum = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::OTC)
            ->with('wallet.currency')
            ->get()
            ->sum(function ($transaction) {
                return $transaction->wallet && $transaction->wallet->currency
                    ? $transaction->amount * $transaction->wallet->currency->exchangePrice
                    : 0;
            });

        // Calculate sum of withdrawal fee transactions
        $withdrawalFeeTransactionsSum = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::WITHDRAWAL_EXCHANGE_FEE)
            ->with('wallet.currency')
            ->get()
            ->sum(function ($transaction) {
                return $transaction->wallet && $transaction->wallet->currency
                    ? $transaction->amount * $transaction->wallet->currency->exchangePrice
                    : 0;
            });

        return view('dashboard.transaction.index', [
            'transactions' => $transactions,
            'referralTransactionsCount' => $referralTransactionsCount,
            'OTCFeeTransactionsCount' => $OTCFeeTransactionsCount,
            'withdrawalFeeTransactionsCount' => $withdrawalFeeTransactionsCount,
            'OTCFeeTransactionsSum' => $OTCFeeTransactionsSum,
            'withdrawalFeeTransactionsSum' => $withdrawalFeeTransactionsSum
        ]);
    }
}
