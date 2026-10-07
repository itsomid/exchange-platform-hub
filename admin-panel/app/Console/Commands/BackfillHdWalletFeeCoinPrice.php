<?php

namespace App\Console\Commands;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\Math;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillHdWalletFeeCoinPrice extends Command
{
    protected $signature = 'withdrawals:backfill-hd-wallet-fee-coin-price
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Fill missing coin_price on hd_wallet_fee transactions: same coin as withdrawal -> withdrawal tx coin_price (fallback usdt_value / amount); different coin -> nearest-in-time tx of that coin with coin_price.';

    public function handle(): int
    {
        $feeTxs = Transaction::query()
            ->join('withdrawals', 'withdrawals.id', '=', 'transactions.withdrawal_id')
            ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->where('transactions.type', TransactionTypeEnum::FEE->value)
            ->where('transactions.subtype', TransactionSubTypeEnum::HD_WALLET_FEE->value)
            ->where(function ($query) {
                $query->whereNull('transactions.coin_price')
                    ->orWhere('transactions.coin_price', 0);
            })
            ->orderBy('transactions.id')
            ->get([
                'transactions.id',
                'transactions.withdrawal_id',
                'transactions.amount',
                'transactions.created_at',
                'wallets.currency_symbol as fee_symbol',
                'withdrawals.currency_symbol as withdrawal_symbol',
                'withdrawals.amount as withdrawal_amount',
                'withdrawals.usdt_value',
            ]);

        $resolved = collect();
        $unresolved = collect();

        foreach ($feeTxs as $tx) {
            $result = $tx->fee_symbol === $tx->withdrawal_symbol
                ? $this->sameCoinPrice($tx)
                : $this->nearestCoinPrice($tx);

            if ($result === null) {
                $unresolved->push($tx);

                continue;
            }

            $resolved->push(['tx' => $tx] + $result);
        }

        $this->info('hd_wallet_fee transactions missing coin_price: '.$feeTxs->count());
        $this->info('Resolvable: '.$resolved->count());
        $this->info('Unresolvable (skipped): '.$unresolved->count());
        $this->newLine();

        if ($resolved->isNotEmpty()) {
            $this->table(
                ['tx_id', 'withdrawal_id', 'fee_symbol', 'w_symbol', 'amount', 'new_coin_price', 'source', 'gap'],
                $resolved->take(30)->map(fn (array $row) => [
                    $row['tx']->id,
                    $row['tx']->withdrawal_id,
                    $row['tx']->fee_symbol,
                    $row['tx']->withdrawal_symbol,
                    $row['tx']->amount,
                    $row['price'],
                    $row['source'],
                    $row['gap'] ?? '-',
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
            $this->warn('To apply: php artisan withdrawals:backfill-hd-wallet-fee-coin-price --execute');

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

        Log::info('withdrawals:backfill-hd-wallet-fee-coin-price', [
            'updated' => $updated,
            'rows' => $resolved->map(fn (array $row) => [
                'id' => $row['tx']->id,
                'withdrawal_id' => $row['tx']->withdrawal_id,
                'coin_price' => $row['price'],
                'source' => $row['source'],
            ])->all(),
        ]);

        $this->info("Updated coin_price on {$updated} transaction(s).");

        return self::SUCCESS;
    }

    /**
     * @return array{price: string, source: string, gap?: string}|null
     */
    private function sameCoinPrice(Transaction $tx): ?array
    {
        $withdrawalTxPrice = Transaction::query()
            ->where('withdrawal_id', $tx->withdrawal_id)
            ->where('type', TransactionTypeEnum::WITHDRAWAL->value)
            ->where('coin_price', '>', 0)
            ->value('coin_price');

        if ($withdrawalTxPrice) {
            return ['price' => (string) $withdrawalTxPrice, 'source' => 'withdrawal_tx'];
        }

        if ($tx->usdt_value > 0 && $tx->withdrawal_amount > 0) {
            return [
                'price' => Math::div($tx->usdt_value, $tx->withdrawal_amount),
                'source' => 'usdt_value/amount',
            ];
        }

        return null;
    }

    /**
     * @return array{price: string, source: string, gap: string}|null
     */
    private function nearestCoinPrice(Transaction $tx): ?array
    {
        $base = fn () => Transaction::query()
            ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->where('wallets.currency_symbol', $tx->fee_symbol)
            ->where('transactions.coin_price', '>', 0)
            ->where('transactions.id', '!=', $tx->id)
            ->select(['transactions.id', 'transactions.coin_price', 'transactions.created_at']);

        $before = $base()->where('transactions.created_at', '<=', $tx->created_at)
            ->orderByDesc('transactions.created_at')->first();
        $after = $base()->where('transactions.created_at', '>=', $tx->created_at)
            ->orderBy('transactions.created_at')->first();

        $txTime = Carbon::parse($tx->created_at);
        $nearest = collect([$before, $after])
            ->filter()
            ->sortBy(fn (Transaction $candidate) => abs($txTime->diffInSeconds(Carbon::parse($candidate->created_at))))
            ->first();

        if (! $nearest) {
            return null;
        }

        return [
            'price' => (string) $nearest->coin_price,
            'source' => 'nearest_tx#'.$nearest->id,
            'gap' => Carbon::parse($nearest->created_at)->diffForHumans($txTime, true),
        ];
    }
}
