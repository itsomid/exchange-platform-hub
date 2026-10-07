<?php

namespace App\Console\Commands;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillBotTransactionCoinPrice extends Command
{
    protected $signature = 'bot:backfill-transaction-coin-price
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Fill missing coin_price on bot transactions (type=bot and bot referral commissions): USDT wallet -> 1, other wallet -> bot_buy_executions.avg_buy_price.';

    public function handle(): int
    {
        // Same rule api-service SettlementService / BotWalletService use for new rows.
        $botTxs = Transaction::query()
            ->leftJoin('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->leftJoin('bot_buy_executions', 'bot_buy_executions.id', '=', 'transactions.bot_buy_execution_id')
            ->where(function ($query) {
                $query->where('transactions.type', TransactionTypeEnum::BOT->value)
                    ->orWhere(function ($referral) {
                        $referral->where('transactions.type', TransactionTypeEnum::REFERRAL->value)
                            ->where('transactions.subtype', TransactionSubTypeEnum::BOT_REFERRAL_COMMISSION->value);
                    });
            })
            ->where(function ($query) {
                $query->whereNull('transactions.coin_price')
                    ->orWhere('transactions.coin_price', 0);
            })
            ->orderBy('transactions.id')
            ->get([
                'transactions.id',
                'transactions.type',
                'transactions.subtype',
                'transactions.amount',
                'transactions.bot_buy_execution_id',
                'wallets.currency_symbol as wallet_symbol',
                'bot_buy_executions.avg_buy_price',
            ]);

        $resolved = collect();
        $unresolved = collect();

        foreach ($botTxs as $tx) {
            if ($tx->wallet_symbol === 'USDT') {
                $resolved->push(['tx' => $tx, 'price' => '1', 'source' => 'usdt']);
            } elseif ($tx->avg_buy_price > 0) {
                $resolved->push(['tx' => $tx, 'price' => (string) $tx->avg_buy_price, 'source' => 'execution#'.$tx->bot_buy_execution_id]);
            } else {
                $unresolved->push($tx);
            }
        }

        $this->info('Bot transactions missing coin_price: '.$botTxs->count());
        $this->info('Resolvable: '.$resolved->count());
        $this->info('Unresolvable (skipped): '.$unresolved->count());
        $this->newLine();

        if ($botTxs->isNotEmpty()) {
            $this->table(
                ['subtype', 'wallet_symbol', 'count'],
                $botTxs->groupBy(fn (Transaction $tx) => $tx->subtype?->value.'|'.$tx->wallet_symbol)
                    ->map(fn ($group, $key) => [...explode('|', $key), $group->count()])
                    ->values()
                    ->all()
            );
            $this->newLine();
        }

        if ($resolved->isNotEmpty()) {
            $this->table(
                ['tx_id', 'subtype', 'wallet_symbol', 'amount', 'new_coin_price', 'source'],
                $resolved->take(30)->map(fn (array $row) => [
                    $row['tx']->id,
                    $row['tx']->subtype?->value,
                    $row['tx']->wallet_symbol,
                    $row['tx']->amount,
                    $row['price'],
                    $row['source'],
                ])->all()
            );

            if ($resolved->count() > 30) {
                $this->comment('Showing first 30 of '.$resolved->count().' transactions.');
            }
            $this->newLine();
        }

        if ($unresolved->isNotEmpty()) {
            $this->warn('Unresolvable tx ids: '.$unresolved->pluck('id')->implode(', '));
            $this->newLine();
        }

        if ($resolved->isEmpty()) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        if (! $this->option('execute')) {
            $this->warn('Dry-run only. No rows were changed.');
            $this->warn('To apply: php artisan bot:backfill-transaction-coin-price --execute');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Set coin_price on {$resolved->count()} transaction(s)?")) {
            $this->info('Cancelled. No rows were changed.');

            return self::SUCCESS;
        }

        $updated = DB::transaction(function () use ($resolved) {
            $updated = 0;
            foreach ($resolved as $row) {
                $updated += Transaction::query()
                    ->where('id', $row['tx']->id)
                    ->update(['coin_price' => $row['price']]);
            }

            return $updated;
        });

        Log::info('bot:backfill-transaction-coin-price', [
            'updated' => $updated,
            'rows' => $resolved->map(fn (array $row) => [
                'id' => $row['tx']->id,
                'coin_price' => $row['price'],
                'source' => $row['source'],
            ])->all(),
        ]);

        $this->info("Updated coin_price on {$updated} transaction(s).");

        return self::SUCCESS;
    }
}
