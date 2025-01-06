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
        $transaction = Transaction::with(['user', 'wallet', 'admin', 'wallet.currency', 'deposit', 'withdrawal'])->filterBy(request()->all())->paginate(50);
        $referralTransactionsCount = Transaction::where('type', TransactionTypeEnum::REFERRAL)->count();

        $OTCFeeTransactionsCount = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::OTC)->count();

        $withdrawalFeeTransactionsCount = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::WITHDRAWAL_FEE)->count();
        return view('dashboard.transaction.index', [
            'transactions' => $transaction,
            'referralTransactionsCount' => $referralTransactionsCount,
            'OTCFeeTransactionsCount' => $OTCFeeTransactionsCount,
            'withdrawalFeeTransactionsCount' => $withdrawalFeeTransactionsCount
        ]);
    }
}
