<?php

namespace App\Console\Commands;

use App\Enums\TransactionSubTypeEnum;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FixStockExchangePurchaseTransactionType extends Command
{
    protected $signature = 'stock:fix-exchange-transaction-types
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Fix exchange-side stock transaction types: purchase buy→sell, admin cancel sell→buy. Does not change amount, balance, or wallets.';

    /**
     * @return array<int, array{label: string, from: string, to: string, amount_operator: string, description_like: string}>
     */
    private function fixes(): array
    {
        return [
            [
                'label' => 'Purchase (exchange sold stock): type buy → sell',
                'from' => 'buy',
                'to' => 'sell',
                'amount_operator' => '>',
                'description_like' => 'بابت خرید سهام شماره%',
            ],
            [
                'label' => 'Admin cancel (exchange bought stock back): type sell → buy',
                'from' => 'sell',
                'to' => 'buy',
                'amount_operator' => '<',
                'description_like' => 'بابت لغو سهام توسط ادمین%',
            ],
        ];
    }

    public function handle(): int
    {
        $exchangeUserId = (int) config('bitexroom.user_id');

        if ($exchangeUserId < 1) {
            $this->error('BITEXROOM_USER_ID / config(bitexroom.user_id) is missing or invalid. Aborting.');

            return self::FAILURE;
        }

        $this->info("Exchange user_id = {$exchangeUserId} (BITEXROOM_USER_ID)");
        $this->comment('Shared filters: subtype=stock, stock_contract_id IS NOT NULL, deleted_at IS NULL. Amount/balance/wallets are not changed.');
        $this->newLine();

        $batches = [];
        $total = 0;

        foreach ($this->fixes() as $fix) {
            $query = $this->matchingQuery($exchangeUserId, $fix);
            $count = $query->count();
            $rows = $count > 0
                ? $query->get(['id', 'user_id', 'wallet_id', 'stock_contract_id', 'amount', 'type', 'subtype', 'description', 'created_at'])
                : collect();

            $this->info($fix['label']);
            $this->line("  type {$fix['from']} → {$fix['to']}, amount {$fix['amount_operator']} 0, description LIKE \"{$fix['description_like']}\"");
            $this->info("  Matching rows: {$count}");

            if ($count > 0) {
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
            }

            $this->newLine();

            $batches[] = ['fix' => $fix, 'ids' => $rows->pluck('id')->all(), 'count' => $count];
            $total += $count;
        }

        if ($total === 0) {
            $this->info('No matching transactions. Nothing to do.');

            return self::SUCCESS;
        }

        if (! $this->option('execute')) {
            $this->warn("Dry-run only. {$total} row(s) would be updated. No rows were changed.");
            $this->warn('To apply: php artisan stock:fix-exchange-transaction-types --execute');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Update type on {$total} row(s)?")) {
            $this->info('Cancelled. No rows were updated.');

            return self::SUCCESS;
        }

        $updatedByFix = DB::transaction(function () use ($batches, $exchangeUserId) {
            $updated = [];

            foreach ($batches as $batch) {
                if ($batch['count'] === 0) {
                    $updated[$batch['fix']['label']] = 0;

                    continue;
                }

                $updated[$batch['fix']['label']] = $this->matchingQuery($exchangeUserId, $batch['fix'])
                    ->whereIn('id', $batch['ids'])
                    ->update(['type' => $batch['fix']['to']]);
            }

            return $updated;
        });

        $updatedTotal = array_sum($updatedByFix);

        Log::info('stock:fix-exchange-transaction-types updated transactions', [
            'updated_total' => $updatedTotal,
            'updated_by_fix' => $updatedByFix,
            'ids' => collect($batches)->pluck('ids')->flatten()->values()->all(),
            'exchange_user_id' => $exchangeUserId,
        ]);

        foreach ($updatedByFix as $label => $count) {
            $this->info("Updated {$count} row(s): {$label}");
        }

        $this->info("Done. {$updatedTotal} row(s) updated. Only the type column changed.");

        return self::SUCCESS;
    }

    /**
     * @param  array{from: string, amount_operator: string, description_like: string}  $fix
     */
    private function matchingQuery(int $exchangeUserId, array $fix)
    {
        return Transaction::query()
            ->where('user_id', $exchangeUserId)
            ->where('type', $fix['from'])
            ->where('subtype', TransactionSubTypeEnum::STOCK->value)
            ->where('amount', $fix['amount_operator'], 0)
            ->whereNotNull('stock_contract_id')
            ->where('description', 'like', $fix['description_like'])
            ->orderBy('id');
    }
}
