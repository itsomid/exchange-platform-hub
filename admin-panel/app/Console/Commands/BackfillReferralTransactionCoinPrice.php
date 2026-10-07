<?php

namespace App\Console\Commands;

use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillReferralTransactionCoinPrice extends Command
{
    protected $signature = 'referrals:backfill-transaction-coin-price
                            {--execute : Apply the update. Without this flag the command only previews matching rows}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Fill missing coin_price on OTC referral transactions with the price of the wallet coin (the OTC quote currency): USDT -> 1, otherwise nearest-in-time tx of that coin with coin_price.';

    public function handle(): int
    {
        // Commission is paid in the OTC quote currency (user side) and from the USDT exchange wallet,
        // so coin_price is the price of the wallet's coin, not otc_orders.price.
        $referralTxs = Transaction::query()
            ->join('otc_orders', 'otc_orders.id', '=', 'transactions.otc_order_id')
            ->join('markets', 'markets.id', '=', 'otc_orders.market_id')
            ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->where('transactions.type', TransactionTypeEnum::REFERRAL->value)
            ->where(function ($query) {
                $query->whereNull('transactions.coin_price')
                    ->orWhere('transactions.coin_price', 0);
            })
            ->orderBy('transactions.id')
            ->get([
                'transactions.id',
                'transactions.otc_order_id',
                'transactions.amount',
                'transactions.created_at',
                'wallets.currency_symbol as wallet_symbol',
                'markets.base_currency',
                'markets.quote_currency',
            ]);

        $resolved = collect();
        $unresolved = collect();

        foreach ($referralTxs as $tx) {
            $result = $tx->wallet_symbol === 'USDT'
                ? ['price' => '1', 'source' => 'usdt']
                : $this->nearestCoinPrice($tx);

            if ($result === null) {
                $unresolved->push($tx);

                continue;
            }

            $resolved->push(['tx' => $tx] + $result);
        }

        $this->info('OTC referral transactions missing coin_price: '.$referralTxs->count());
        $this->info('Resolvable: '.$resolved->count());
        $this->info('Unresolvable (skipped): '.$unresolved->count());
        $this->newLine();

        if ($resolved->isNotEmpty()) {
            $this->table(
                ['tx_id', 'otc_order_id', 'market', 'wallet_symbol', 'amount', 'new_coin_price', 'source', 'gap'],
                $resolved->take(30)->map(fn (array $row) => [
                    $row['tx']->id,
                    $row['tx']->otc_order_id,
                    $row['tx']->base_currency.$row['tx']->quote_currency,
                    $row['tx']->wallet_symbol,
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
            $this->warn('To apply: php artisan referrals:backfill-transaction-coin-price --execute');

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

        Log::info('referrals:backfill-transaction-coin-price', [
            'updated' => $updated,
            'rows' => $resolved->map(fn (array $row) => [
                'id' => $row['tx']->id,
                'otc_order_id' => $row['tx']->otc_order_id,
                'coin_price' => $row['price'],
                'source' => $row['source'],
            ])->all(),
        ]);

        $this->info("Updated coin_price on {$updated} transaction(s).");

        return self::SUCCESS;
    }

    /**
     * @return array{price: string, source: string, gap: string}|null
     */
    private function nearestCoinPrice(Transaction $tx): ?array
    {
        $base = fn () => Transaction::query()
            ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->where('wallets.currency_symbol', $tx->wallet_symbol)
            ->where('transactions.type', '!=', TransactionTypeEnum::REFERRAL->value)
            ->where('transactions.coin_price', '>', 0)
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
