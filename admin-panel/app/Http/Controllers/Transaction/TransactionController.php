<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exports\TransactionExport;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
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
            ->where('subtype', TransactionSubTypeEnum::EXCHANGE_WITHDRAWAL_FEE)->count();


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
            ->where('subtype', TransactionSubTypeEnum::EXCHANGE_WITHDRAWAL_FEE)
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
        $filename = 'transactions_' . now()->format('Y-m-d_H-i-s');

        // Build query with filters
        $transactionQuery = Transaction::query()
            ->with(['user:id,email,username', 'wallet:id,currency_symbol', 'admin:id,first_name,last_name'])
            ->orderBy('id')
            ->filterBy($request->all());

        // Check total records to prevent memory issues
        $totalRecords = $transactionQuery->count();
        
        // Set a reasonable limit (100,000 records max)
        if ($totalRecords > 100000) {
            return redirect()->back()->with('error', 'تعداد رکوردها بیش از حد مجاز است (' . number_format($totalRecords) . ' رکورد). لطفاً فیلترهای بیشتری اعمال کنید. (حداکثر: 100,000 رکورد)');
        }

        // Use chunk to process data efficiently and prevent memory overflow
        $transactions = collect();
        
        $transactionQuery->chunk(1000, function ($chunk) use (&$transactions) {
            foreach ($chunk as $transaction) {
                $transactions->push([
                    $transaction->id,
                    $transaction->type->label(),
                    $transaction->subtype->label(),
                    $transaction->user->email ?? 'N/A',
                    $transaction->wallet->currency_symbol ?? 'N/A',
                    $transaction->amount,
                    $transaction->balance,
                    $transaction->description,
                    $transaction->created_at,
                    $transaction->status->label(),
                    $transaction->admin_id && $transaction->admin ? $transaction->admin->fullname() : 'خیر',
                    $transaction->admin_description
                ]);
            }
        });

        return Excel::download(new TransactionExport($transactions), $filename . '.xlsx');
    }
}
