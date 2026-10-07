<?php

namespace App\Console\Commands;

use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillDepositTransactionCoinPrice extends Command
{
    protected $signature = 'deposits:backfill-transaction-coin-price
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Fill missing coin_price on deposit transactions using deposits.usdt_value / deposits.amount.';

    public function handle(): int
    {
        $rows = Transaction::query()
            ->join('deposits', 'deposits.id', '=', 'transactions.deposit_id')
            ->where('transactions.type', TransactionTypeEnum::DEPOSIT->value)
            ->where(function ($query) {
                $query->whereNull('transactions.coin_price')
                    ->orWhere('transactions.coin_price', 0);
            })
            ->whereNotNull('deposits.usdt_value')
            ->where('deposits.usdt_value', '>', 0)
            ->where('deposits.amount', '>', 0)
            ->orderBy('transactions.id')
            ->get([
                'transactions.id',
                'transactions.deposit_id',
                'transactions.amount',
                'transactions.coin_price',
                'deposits.currency_symbol',
                'deposits.amount as deposit_amount',
                'deposits.usdt_value',
                DB::raw('deposits.usdt_value / deposits.amount as derived_price'),
            ]);

        $this->info('Deposit transactions missing coin_price that can be backfilled: '.$rows->count());
        $this->newLine();

        if ($rows->isEmpty()) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        $this->table(
            ['tx_id', 'deposit_id', 'symbol', 'tx_amount', 'd_amount', 'd_usdt_value', 'new_coin_price'],
            $rows->take(20)->map(fn (Transaction $tx) => [
                $tx->id,
                $tx->deposit_id,
                $tx->currency_symbol,
                $tx->amount,
                $tx->deposit_amount,
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
            $this->warn('To apply: php artisan deposits:backfill-transaction-coin-price --execute');

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

        Log::info('deposits:backfill-transaction-coin-price', [
            'updated' => $updated,
            'rows' => $rows->map(fn (Transaction $tx) => [
                'id' => $tx->id,
                'deposit_id' => $tx->deposit_id,
                'coin_price' => $tx->derived_price,
            ])->all(),
        ]);

        $this->info("Updated coin_price on {$updated} transaction(s).");

        return self::SUCCESS;
    }
}
