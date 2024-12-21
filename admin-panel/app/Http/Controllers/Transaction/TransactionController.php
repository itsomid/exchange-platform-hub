<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        $transaction = Transaction::filterBy(request()->all())->paginate(50)->all();
//        return $transaction[0]->wallet->currency;
        return view('dashboard.transaction.index',['transactions' => $transaction]);
    }
}
