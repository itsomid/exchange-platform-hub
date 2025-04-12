<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exports\TransactionExport;
use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


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

    public function excelExport(Request $request)
    {
        $from = $request->get('from_id');
        $to = $request->get('to_id');
        $filename = 'transactions_' . $from . '_' . $to;

        $transactionQuery = Transaction::orderBy('id')->filterBy(request()->all());
        if ($request->get('from_id') && $request->get('to_id')) {
            $transactionQuery->where('id', '>=', $request->from_id)
                ->where('id', '<=', $request->to_id);
        }
        $transactions = $transactionQuery->get();

        $transactions = $transactions->map(function (Transaction $transaction) {
            return [
                $transaction->id,
                $transaction->type->label(),
                $transaction->subtype->label(),
                $transaction->user->email,
                $transaction->wallet->currency_symbol,
                $transaction->amount,
                $transaction->balance,
                $transaction->description,
                $transaction->created_at,
                $transaction->status->label(),
                $transaction->admin_id ? $transaction->admin->fullname() : 'خیر',
                $transaction->admin_description


            ];
        });

        return Excel::download(new TransactionExport($transactions), $filename . '.xlsx');
    }
}
