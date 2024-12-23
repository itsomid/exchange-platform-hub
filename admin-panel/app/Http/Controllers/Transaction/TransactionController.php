<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        $transaction = Transaction::with(['user', 'wallet'])->filterBy(request()->all())->paginate(50);
        $referralTransactionsCount = Transaction::where('type', TransactionTypeEnum::REFERRAL)->count();
//        return $transaction[0]->wallet->currency;
        return view('dashboard.transaction.index', [
            'transactions' => $transaction,
            'referralTransactionsCount' => $referralTransactionsCount
        ]);
    }
}
