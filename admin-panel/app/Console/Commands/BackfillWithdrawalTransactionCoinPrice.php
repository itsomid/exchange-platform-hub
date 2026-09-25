<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillWithdrawalTransactionCoinPrice extends Command
{
    protected $signature = 'withdrawals:backfill-transaction-coin-price
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Fill missing coin_price on withdrawal-linked transactions using withdrawals.usdt_value / withdrawals.amount.';

    public function handle(): int
    {
        // Only transactions in the same coin as the withdrawal (user withdrawal + exchange withdrawal fee);
        // e.g. HD wallet fees paid in the parent coin are excluded.
        $rows = Transaction::query()
            ->join('withdrawals', 'withdrawals.id', '=', 'transactions.withdrawal_id')
            ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->whereColumn('wallets.currency_symbol', 'withdrawals.currency_symbol')
            ->where(function ($query) {
                $query->whereNull('transactions.coin_price')
                    ->orWhere('transactions.coin_price', 0);
            })
            ->whereNotNull('withdrawals.usdt_value')
            ->where('withdrawals.amount', '>', 0)
            ->orderBy('transactions.id')
            ->get([
                'transactions.id',
                'transactions.withdrawal_id',
                'transactions.type',
                'transactions.amount',
                'transactions.coin_price',
                'withdrawals.currency_symbol',
                'withdrawals.amount as withdrawal_amount',
                'withdrawals.usdt_value',
                DB::raw('withdrawals.usdt_value / withdrawals.amount as derived_price'),
            ]);

        $this->info('Transactions missing coin_price that can be backfilled: '.$rows->count());
        $this->newLine();

        if ($rows->isEmpty()) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        $this->table(
            ['tx_id', 'withdrawal_id', 'type', 'symbol', 'tx_amount', 'w_amount', 'w_usdt_value', 'new_coin_price'],
            $rows->take(20)->map(fn (Transaction $tx) => [
                $tx->id,
                $tx->withdrawal_id,
                $tx->type?->value,
                $tx->currency_symbol,
                $tx->amount,
                $tx->withdrawal_amount,
                $tx->usdt_value,
                $tx->derived_price,
            ])->all()
        );

        if ($rows->count() > 20) {
            $this->comment('Showing first 20 of '.$rows->count().' transactions.');
        }
        $this->newLine();

        if (! $this->option('execute')) {
            $this->warn('Dry-run only. No rows were changed.');
            $this->warn('To apply: php artisan withdrawals:backfill-transaction-coin-price --execute');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Set coin_price on {$rows->count()} transaction(s)?")) {
            $this->info('Cancelled. No rows were changed.');

            return self::SUCCESS;
        }

        $updated = DB::transaction(function () use ($rows) {
            $updated = 0;
            foreach ($rows as $tx) {
                $updated += Transaction::query()
                    ->where('id', $tx->id)
                    ->update(['coin_price' => $tx->derived_price]);
            }

            return $updated;
        });

        Log::info('withdrawals:backfill-transaction-coin-price', [
            'updated' => $updated,
            'rows' => $rows->map(fn (Transaction $tx) => [
                'id' => $tx->id,
                'withdrawal_id' => $tx->withdrawal_id,
                'coin_price' => $tx->derived_price,
            ])->all(),
        ]);

        $this->info("Updated coin_price on {$updated} transaction(s).");

        return self::SUCCESS;
    }
}
