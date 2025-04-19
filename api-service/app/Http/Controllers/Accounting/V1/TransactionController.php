<?php

namespace App\Http\Controllers\Accounting\V1;

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

    public function index()
    {
        $transactions = Transaction::query()
            ->with('user', 'wallet')
            ->whereNull('journal_entry_number')
            ->latest()
            ->get();

        return TransactionResource::collection($transactions);
    }
}
