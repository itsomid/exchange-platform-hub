<?php

namespace App\Console\Commands;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FixStockExchangePurchaseTransactionType extends Command
{
    protected $signature = 'stock:fix-exchange-purchase-type
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Change exchange-side stock purchase transactions from type=buy to type=sell. Does not change amount, balance, or wallets.';

    public function handle(): int
    {
        $exchangeUserId = (int) config('bitexroom.user_id');

        if ($exchangeUserId < 1) {
            $this->error('BITEXROOM_USER_ID / config(bitexroom.user_id) is missing or invalid. Aborting.');

            return self::FAILURE;
        }

        $this->info('Target: transactions.type buy → sell');
        $this->line('Only these rows match (all conditions AND):');
        $this->line("  user_id            = {$exchangeUserId}  (exchange user from BITEXROOM_USER_ID)");
        $this->line('  type               = buy');
        $this->line('  subtype            = stock');
        $this->line('  amount             > 0          (incoming USDT = sale of stock)');
        $this->line('  stock_contract_id  IS NOT NULL');
        $this->line('  deleted_at         IS NULL');
        $this->line('  description        LIKE "بابت خرید سهام شماره%"');
        $this->newLine();
        $this->comment('Not touched: user BUY rows, cancellation rows (amount < 0), fee rows, admin-created SELL rows, amount/balance/wallets.');

        $query = Transaction::query()
            ->where('user_id', $exchangeUserId)
            ->where('type', TransactionTypeEnum::BUY->value)
            ->where('subtype', TransactionSubTypeEnum::STOCK->value)
            ->where('amount', '>', 0)
            ->whereNotNull('stock_contract_id')
            ->where('description', 'like', 'بابت خرید سهام شماره%')
            ->orderBy('id');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No matching transactions. Nothing to do.');

            return self::SUCCESS;
        }

        $rows = $query->get(['id', 'user_id', 'wallet_id', 'stock_contract_id', 'amount', 'type', 'subtype', 'description', 'created_at']);

        $this->info("Matching rows: {$count}");
        $this->table(
            ['id', 'user_id', 'wallet_id', 'stock_contract_id', 'amount', 'type', 'created_at', 'description'],
            $rows->map(fn (Transaction $tx) => [
                $tx->id,
                $tx->user_id,
                $tx->wallet_id,
                $tx->stock_contract_id,
                $tx->amount,
                $tx->type->value,
                $tx->created_at,
                Str::limit((string) $tx->description, 80),
            ])->all()
        );

        if (! $this->option('execute')) {
            $this->warn('Dry-run only. No rows were updated.');
            $this->warn('To apply: php artisan stock:fix-exchange-purchase-type --execute');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Update type buy → sell on {$count} row(s)?")) {
            $this->info('Cancelled. No rows were updated.');

            return self::SUCCESS;
        }

        $ids = $rows->pluck('id')->all();

        $updated = DB::transaction(function () use ($ids, $exchangeUserId) {
            return Transaction::query()
                ->whereIn('id', $ids)
                ->where('user_id', $exchangeUserId)
                ->where('type', TransactionTypeEnum::BUY->value)
                ->where('subtype', TransactionSubTypeEnum::STOCK->value)
                ->where('amount', '>', 0)
                ->whereNotNull('stock_contract_id')
                ->where('description', 'like', 'بابت خرید سهام شماره%')
                ->update(['type' => TransactionTypeEnum::SELL->value]);
        });

        Log::info('stock:fix-exchange-purchase-type updated transactions', [
            'updated' => $updated,
            'ids' => $ids,
            'exchange_user_id' => $exchangeUserId,
        ]);

        $this->info("Updated {$updated} row(s). Only the type column changed (buy → sell).");

        return self::SUCCESS;
    }
}
