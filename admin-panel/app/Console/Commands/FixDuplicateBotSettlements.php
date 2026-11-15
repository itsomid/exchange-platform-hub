<?php

namespace App\Console\Commands;

use App\Enums\TransactionSubTypeEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Removes bot_trade_settlements rows that settled a sell tier a second (or
 * third) time — left by parallel admin cancel-all runs before the cancel flow
 * locked its tiers — and undoes what each duplicate row wrote:
 *   - its fee transactions (network fee on the user; exchange and
 *     performance fee on the Bitexroom account),
 *   - the performance fee it credited to the Bitexroom USDT wallet,
 *   - the positive P/L it added to the bot wallet's profit_balance (a
 *     statistic shown in the admin panel — always corrected),
 *   - with --adjust-wallet, the net P/L it added to the bot wallet balance.
 *
 * Fee transactions already exported to accounting (journal_entry_number set)
 * are never deleted: the user is skipped and reported instead.
 *
 * The first settlement of every tier (lowest id) is kept. Each user is fixed
 * in its own DB transaction. Without --execute everything is rolled back, so
 * the printed report is exactly what --execute would commit.
 */
class FixDuplicateBotSettlements extends Command
{
    protected $signature = 'bot:fix-duplicate-settlements
        {--user=* : User id(s) to fix (required)}
        {--adjust-wallet : Also take the duplicated net P/L back from the bot wallet balance}
        {--execute : Commit the changes (default: dry-run that is rolled back)}';

    protected $description = 'Delete duplicate settlements of a bot sell tier and undo their wallet / fee effects';

    private const SCALE = 8;

    /** A settlement's fee transactions are written right after it, in the same DB transaction. */
    private const TXN_WINDOW_SECONDS = 5;

    public function handle(): int
    {
        $userIds = array_values(array_filter(array_unique(array_map('intval', (array) $this->option('user')))));
        if ($userIds === []) {
            $this->error('Pass at least one --user=ID.');

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $adjust  = (bool) $this->option('adjust-wallet');

        $this->line($execute
            ? '<fg=red;options=bold>Mode: EXECUTE — changes will be committed.</>'
            : '<fg=yellow;options=bold>Mode: DRY-RUN — everything is rolled back. Add --execute to commit.</>');
        $this->line('Bot wallet adjustment: '.($adjust ? 'ON (--adjust-wallet)' : 'OFF'));
        $this->newLine();

        $failed = false;
        foreach ($userIds as $userId) {
            try {
                $report = $this->fixUser($userId, $adjust, $execute);
                $this->printReport($report, $adjust, $execute);
            } catch (\Throwable $e) {
                $failed = true;
                $this->error("User #{$userId}: {$e->getMessage()} — nothing was changed for this user.");
                $this->newLine();
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function fixUser(int $userId, bool $adjust, bool $execute): array
    {
        DB::beginTransaction();

        try {
            $report = $this->applyFix($userId, $adjust);

            if ($execute && $report['rows'] > 0) {
                $report['backup'] = $this->writeBackup($report);
                DB::commit();

                Log::warning('bot.fix_duplicate_settlements', array_diff_key($report, array_flip(['backup_data'])));
            } else {
                DB::rollBack();
            }

            return $report;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    private function applyFix(int $userId, bool $adjust): array
    {
        $exchangeUserId = (int) config('bitexroom.user_id', 1);

        $pnlBefore = $this->num(DB::table('bot_trade_settlements')->where('user_id', $userId)->sum('net_pnl'));

        $duplicateIds = DB::table('bot_trade_settlements as s')
            ->where('s.user_id', $userId)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('bot_trade_settlements as k')
                ->whereColumn('k.bot_sell_order_id', 's.bot_sell_order_id')
                ->whereColumn('k.id', '<', 's.id'))
            ->pluck('s.id');

        $duplicates = DB::table('bot_trade_settlements')
            ->whereIn('id', $duplicateIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $report = [
            'user_id'     => $userId,
            'rows'        => $duplicates->count(),
            'sell_orders' => $duplicates->pluck('bot_sell_order_id')->unique()->count(),
            'pnl_before'  => $pnlBefore,
        ];

        if ($duplicates->isEmpty()) {
            return $report;
        }

        foreach ($duplicates as $s) {
            if (bccomp($this->num($s->referral_fee), '0', self::SCALE) !== 0 || $s->referral_user_id !== null) {
                throw new \RuntimeException("settlement #{$s->id} paid a referral commission; reverse it manually first");
            }
        }

        $netTotal  = '0';
        $posTotal  = '0';
        $perfTotal = '0';
        foreach ($duplicates as $s) {
            $net       = $this->num($s->net_pnl);
            $netTotal  = bcadd($netTotal, $net, self::SCALE);
            $posTotal  = bccomp($net, '0', self::SCALE) > 0 ? bcadd($posTotal, $net, self::SCALE) : $posTotal;
            $perfTotal = bcadd($perfTotal, $this->num($s->performance_fee), self::SCALE);
        }

        [$txnIds, $missing] = $this->matchFeeTransactions($duplicates, $userId, $exchangeUserId);
        $allTxnIds = array_merge(...array_values($txnIds));

        $exported = $allTxnIds === [] ? 0 : DB::table('transactions')
            ->whereIn('id', $allTxnIds)
            ->whereNotNull('journal_entry_number')
            ->count();
        if ($exported > 0) {
            throw new \RuntimeException("{$exported} of its duplicate fee transactions are already in accounting (journal_entry_number set); they need reversal entries, not deletion");
        }

        $report['backup_data'] = [
            'settlements'  => $duplicates->all(),
            'transactions' => $allTxnIds === [] ? [] : DB::table('transactions')->whereIn('id', $allTxnIds)->orderBy('id')->get()->all(),
        ];

        foreach (array_chunk($allTxnIds, 500) as $chunk) {
            DB::table('transactions')->whereIn('id', $chunk)->delete();
        }
        DB::table('bot_trade_settlements')->whereIn('id', $duplicates->pluck('id'))->delete();

        // The duplicate performance fee was credited to the Bitexroom USDT wallet.
        if (bccomp($perfTotal, '0', self::SCALE) > 0) {
            $exchangeWallet = DB::table('wallets')
                ->where('user_id', $exchangeUserId)
                ->where('currency_symbol', 'USDT')
                ->lockForUpdate()
                ->first();
            if (! $exchangeWallet) {
                throw new \RuntimeException("Bitexroom USDT wallet (user #{$exchangeUserId}) not found");
            }
            $exchangeAfter = bcsub($this->num($exchangeWallet->balance), $perfTotal, self::SCALE);
            DB::table('wallets')->where('id', $exchangeWallet->id)->update(['balance' => $exchangeAfter]);

            $report['exchange_wallet'] = [$this->num($exchangeWallet->balance), $exchangeAfter];
        }

        $botWallet = DB::table('bot_wallets')->where('user_id', $userId)->lockForUpdate()->first();
        if (! $botWallet) {
            throw new \RuntimeException('bot wallet not found');
        }
        $balanceBefore = $this->num($botWallet->balance);
        $balanceAfter  = $adjust ? bcsub($balanceBefore, $netTotal, self::SCALE) : $balanceBefore;
        $profitAfter   = bcsub($this->num($botWallet->profit_balance), $posTotal, self::SCALE);
        DB::table('bot_wallets')->where('id', $botWallet->id)->update([
            'balance'        => $balanceAfter,
            'profit_balance' => $profitAfter,
            'updated_at'     => now(),
        ]);

        $report['bot_wallet'] = [
            'balance'        => [$balanceBefore, $balanceAfter],
            'profit_balance' => [$this->num($botWallet->profit_balance), $profitAfter],
            'locked_balance' => $this->num($botWallet->locked_balance),
            'free_after'     => bcsub($balanceAfter, $this->num($botWallet->locked_balance), self::SCALE),
        ];

        $left = DB::table('bot_trade_settlements')
            ->where('user_id', $userId)
            ->groupBy('bot_sell_order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('bot_sell_order_id');
        if ($left->isNotEmpty()) {
            throw new \RuntimeException('duplicates still present after delete (sell orders '.$left->implode(', ').')');
        }

        return $report + [
            'net_total'  => $netTotal,
            'pos_total'  => $posTotal,
            'perf_total' => $perfTotal,
            'txns'       => array_map('count', $txnIds),
            'missing'    => $missing,
            'pnl_after'  => $this->num(DB::table('bot_trade_settlements')->where('user_id', $userId)->sum('net_pnl')),
        ];
    }

    /**
     * Find the fee transactions each duplicate settlement wrote (see
     * SettlementService::writeTxns in api-service): same execution, same
     * amount, written within a few seconds after the settlement row.
     * Rows with identical keys are interchangeable, so each one is claimed once.
     *
     * @return array{0: array<string, list<int>>, 1: array<string, int>}
     */
    private function matchFeeTransactions(Collection $duplicates, int $userId, int $exchangeUserId): array
    {
        $executions = DB::table('bot_buy_executions')
            ->whereIn('id', $duplicates->pluck('bot_buy_execution_id')->unique())
            ->get(['id', 'avg_buy_price', 'buy_ref_exchange_fee_currency'])
            ->keyBy('id');

        $txnIds  = ['network' => [], 'exchange' => [], 'performance' => []];
        $missing = [];
        $claimed = [0];

        foreach ($duplicates as $s) {
            $wanted = [];
            if (bccomp($this->num($s->network_fee), '0', self::SCALE) > 0) {
                $wanted['network'] = [TransactionSubTypeEnum::BOT_NETWORK_FEE->value, $userId, $this->num($s->network_fee)];
            }
            if (bccomp($this->num($s->exchange_fee), '0', self::SCALE) > 0) {
                $wanted['exchange'] = [
                    TransactionSubTypeEnum::BOT_EXCHANGE_FEE->value,
                    $exchangeUserId,
                    $this->exchangeFeeTxnAmount($executions[$s->bot_buy_execution_id], $this->num($s->exchange_fee)),
                ];
            }
            if (bccomp($this->num($s->performance_fee), '0', self::SCALE) > 0) {
                $wanted['performance'] = [TransactionSubTypeEnum::BOT_PERFORMANCE_FEE->value, $exchangeUserId, $this->num($s->performance_fee)];
            }

            $from = Carbon::parse($s->created_at);
            foreach ($wanted as $kind => [$subtype, $txnUserId, $amount]) {
                $id = DB::table('transactions')
                    ->where('bot_buy_execution_id', $s->bot_buy_execution_id)
                    ->where('subtype', $subtype)
                    ->where('user_id', $txnUserId)
                    ->where('amount', $amount)
                    ->whereBetween('created_at', [$from, $from->copy()->addSeconds(self::TXN_WINDOW_SECONDS)])
                    ->whereNotIn('id', $claimed)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->value('id');

                if ($id === null) {
                    $missing[$kind] = ($missing[$kind] ?? 0) + 1;

                    continue;
                }
                $claimed[]       = $id;
                $txnIds[$kind][] = $id;
            }
        }

        return [$txnIds, $missing];
    }

    /** Mirrors SettlementService::recordExchangeFee (api-service): USDT fee converted to the fee coin, negated. */
    private function exchangeFeeTxnAmount(object $execution, string $exchangeFeeUsdt): string
    {
        $feeCurrency = strtoupper((string) ($execution->buy_ref_exchange_fee_currency ?: 'USDT'));
        $avg         = $this->num($execution->avg_buy_price);

        $fee = $exchangeFeeUsdt;
        if ($feeCurrency !== 'USDT' && bccomp($avg, '0', self::SCALE) > 0) {
            $fee = bcdiv($exchangeFeeUsdt, $avg, self::SCALE);
        }

        return bcmul($fee, '-1', self::SCALE);
    }

    private function writeBackup(array $report): string
    {
        $path = sprintf('bot-duplicate-settlements/%s_user_%d.json', now()->format('Ymd_His'), $report['user_id']);
        $data = array_diff_key($report, array_flip(['backup_data'])) + $report['backup_data'];

        if (! Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            throw new \RuntimeException("could not write backup {$path}");
        }

        return Storage::disk('local')->path($path);
    }

    private function printReport(array $r, bool $adjust, bool $execute): void
    {
        $this->line("<options=bold>User #{$r['user_id']}</>");

        if ($r['rows'] === 0) {
            $this->info('  No duplicate settlements.');
            $this->newLine();

            return;
        }

        $rows = [
            ['Duplicate settlement rows deleted', $r['rows']],
            ['Sell tiers affected', $r['sell_orders']],
            ['Duplicated net P/L', $r['net_total']],
            ['Duplicated positive P/L (profit_balance)', $r['pos_total']],
            ['Duplicated performance fee', $r['perf_total']],
            ['Fee transactions deleted', sprintf('network %d, exchange %d, performance %d', $r['txns']['network'], $r['txns']['exchange'], $r['txns']['performance'])],
            ['Fee transactions not found', $r['missing'] === [] ? '0' : json_encode($r['missing'])],
            ['User P/L (sum net_pnl)', "{$r['pnl_before']} → {$r['pnl_after']}"],
        ];
        if (isset($r['exchange_wallet'])) {
            $rows[] = ['Bitexroom USDT wallet', "{$r['exchange_wallet'][0]} → {$r['exchange_wallet'][1]}"];
        }
        $w      = $r['bot_wallet'];
        $rows[] = ['Bot wallet profit_balance', "{$w['profit_balance'][0]} → {$w['profit_balance'][1]}"];
        $rows[] = ['Bot wallet balance', $adjust
            ? "{$w['balance'][0]} → {$w['balance'][1]}"
            : "{$w['balance'][0]} (NOT adjusted — no --adjust-wallet)"];
        $rows[] = ['Bot wallet locked_balance (unchanged)', $w['locked_balance']];
        $rows[] = ['Bot wallet free balance after', $w['free_after']];
        $this->table(['', ''], $rows);

        if (bccomp($r['bot_wallet']['free_after'], '0', self::SCALE) < 0) {
            $this->warn('  Free balance is negative: open positions hold more than the user\'s real balance. '
                .'Transfers out and new buys stay blocked until enough positions close.');
        }
        if ($r['missing'] !== []) {
            $this->warn('  Some fee transactions were not found (already removed?) — the settlements were still deleted.');
        }
        if ($execute) {
            $this->info("  Committed. Backup: {$r['backup']}");
        }
        $this->newLine();
    }

    private function num(mixed $value): string
    {
        $s = trim((string) ($value ?? '0'));
        if ($s === '' || ! is_numeric($s)) {
            return '0';
        }
        if (stripos($s, 'e') !== false) {
            $s = number_format((float) $s, self::SCALE, '.', '');
        }

        return bcadd($s, '0', self::SCALE);
    }
}
