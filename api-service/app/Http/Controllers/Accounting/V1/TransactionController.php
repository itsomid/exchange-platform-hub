<?php

namespace App\Http\Controllers\Accounting\V1;

use App\Http\Requests\Accounting\V1\ListTransactionsRequest;
use App\Http\Requests\Accounting\V1\SaveJournalEntryNumberRequest;
use App\Http\Resources\Accounting\V1\TransactionResource;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionController
{
    public function saveJournalNumber(SaveJournalEntryNumberRequest $request)
    {
        
        $ids = $request->input('transaction_ids');

        $transactions = Transaction::query()->whereIn('id', $ids)->get();

        try {

            DB::beginTransaction();
            foreach ($transactions as $transaction) {
                $transaction->journal_entry_number = $transaction->id + 1_000_000;
                $transaction->save();
            }
            DB::commit();

            return response([
                'message' => 'journal entry number has been successfully saved!',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return response([
                'message' => 'Server has error !',
            ], 500);
        }

    }

    public function index(ListTransactionsRequest $request)
    {
        $referenceIdFilters = [
            'deposit_id',
            'withdrawal_id',
            'otc_order_id',
            'spot_trade_id',
            'stock_contract_id',
            'bot_order_id',
            'bot_buy_execution_id',
            'bot_wallet_transfer_id',
        ];

        $query = Transaction::query()
            ->with('user', 'wallet')
            ->whereNull('journal_entry_number')
            ->when($request->filled('from_id'), fn ($q) => $q->where('id', '>=', $request->integer('from_id')))
            ->when($request->filled('to_id'), fn ($q) => $q->where('id', '<=', $request->integer('to_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('subtype'), fn ($q) => $q->where('subtype', $request->input('subtype')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->input('date')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')));

        foreach ($referenceIdFilters as $column) {
            $query->when(
                $request->filled($column),
                fn ($q) => $q->where($column, $request->integer($column))
            );
        }

        $transactions = $query->orderBy('id')->get();

        return TransactionResource::collection($transactions);
    }
}
