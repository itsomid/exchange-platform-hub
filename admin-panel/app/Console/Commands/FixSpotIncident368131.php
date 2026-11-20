<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Repairs the data left by the 2026-11-15 spot matching incident (trade #368131):
 * the 0.55 DOGE limit SELL of user #53 (order #9341751) was filled against the
 * in-flight market BUY of user #224 (order #9342423), at ~9x the market price.
 *
 * Admins already reversed user #224's side (+119.735 USDT, -217.7 DOGE) and took
 * 119.73 USDT back from user #53 in AUDIO + APE — but valued at the recorded
 * coin prices that came to ~126.04 USDT, and the 217.7 DOGE user #53 sold never
 * went back to them. This command:
 *
 *   1. orders      — closes orders left "open" though fully filled (#9341751,
 *                    #9341265, #9341364) and their 0-amount locks, and sets
 *                    #9342423's filled_quantity to its real fill (477.76).
 *   2. ape         — lowers the APE clawback so AUDIO + APE = 119.735 USDT and
 *                    credits the difference back to user #53. The exchange wallet
 *                    already moved that APE out (admin #20), so the same amount
 *                    is taken off that outflow and its wallet stays unchanged.
 *   3. doge        — the 217.7 DOGE recovered from user #224 goes to user #53
 *                    (its owner) instead of the exchange wallet, and admin #25's
 *                    216.73 DOGE outflow of that DOGE is set to 0.
 *
 * Every row is checked against the values it held before the repair; a row that
 * already holds the repaired values is skipped, anything else aborts the run.
 * Without --execute everything runs inside a transaction that is rolled back.
 */
class FixSpotIncident368131 extends Command
{
    protected $signature = 'spot:fix-incident-368131
        {--execute : Commit the changes (default: dry-run that is rolled back)}';

    protected $description = 'Repair the data left by the 2026-11-15 spot matching incident (trade #368131)';

    private const SCALE = 8;
    private const NOTE  = 'اصلاح حادثه معامله اسپات #368131 (2026-11-15)';

    private const SELLER   = 53;  // nilaj0633, order #9341751
    private const EXCHANGE = 1;

    // wallet ids
    private const SELLER_APE    = 11697;
    private const SELLER_DOGE   = 92;
    private const EXCHANGE_APE  = 11603;
    private const EXCHANGE_DOGE = 17;

    /** @var list<array<string,mixed>> */
    private array $backup = [];

    /** @var list<array{0:string,1:string}> */
    private array $summary = [];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $this->line($execute
            ? '<fg=red;options=bold>Mode: EXECUTE — changes will be committed.</>'
            : '<fg=yellow;options=bold>Mode: DRY-RUN — everything is rolled back. Add --execute to commit.</>');
        $this->newLine();

        DB::beginTransaction();

        try {
            $touched = [210258, 210259, 210310, 210399, 210413, 210650];
            $exported = DB::table('transactions')->whereIn('id', $touched)->whereNotNull('journal_entry_number')->pluck('id');
            if ($exported->isNotEmpty()) {
                throw new \RuntimeException('transactions already in accounting (journal_entry_number set): '.$exported->implode(', ').' — they need reversal entries, not edits');
            }

            $walletIds = [self::SELLER_APE, self::SELLER_DOGE, self::EXCHANGE_APE, self::EXCHANGE_DOGE];
            $before = $this->wallets($walletIds);

            $this->fixOrders();
            $this->fixApeClawback();
            $this->fixDogeReturn();

            $after = $this->wallets($walletIds);

            $this->table(['Step', 'Result'], $this->summary);
            $this->table(['Wallet', 'Before', 'After'], array_map(
                fn ($id) => [$before[$id]['label'], $before[$id]['balance'], $after[$id]['balance']],
                $walletIds
            ));

            if ($execute) {
                $path = sprintf('spot-incident-368131/%s.json', now()->format('Ymd_His'));
                $payload = ['rows' => $this->backup, 'summary' => $this->summary, 'wallets' => compact('before', 'after')];
                if (! Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    throw new \RuntimeException("could not write backup {$path}");
                }
                DB::commit();
                Log::warning('spot.fix_incident_368131', ['summary' => $this->summary]);
                $this->info('Committed. Backup: '.Storage::disk('local')->path($path));
            } else {
                DB::rollBack();
                $this->warn('Rolled back (dry-run).');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage().' — nothing was changed.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function fixOrders(): void
    {
        // Orders whose last fill threw before they were marked completed.
        foreach ([9341751 => 2794, 9341265 => 2461, 9341364 => 2569] as $orderId => $lockId) {
            $order = $this->row('spot_orders', $orderId);
            $closed = $this->edit('spot_orders', $orderId,
                ['status' => 'open', 'filled_quantity' => $order->quantity],
                ['status' => 'completed']);

            if ($closed) {
                $this->edit('locked_balance_details', $lockId,
                    ['spot_order_id' => $orderId, 'amount' => '0', 'deleted_at' => null],
                    ['description' => "تکمیل سفارش اسپات #{$orderId} - ".self::NOTE, 'deleted_at' => now()]);
            }
            $this->summary[] = ["order #{$orderId}", $closed ? 'open → completed, lock #'.$lockId.' removed' : 'already completed'];
        }

        // 217.7 of its 695.46 recorded fill came from #368131 and was reversed.
        $fixed = $this->edit('spot_orders', 9342423, ['filled_quantity' => '217.7'], ['filled_quantity' => '477.76']);
        $this->summary[] = ['order #9342423', $fixed ? 'filled_quantity 217.7 → 477.76' : 'already fixed'];

        $noted = $this->edit('spot_trades', 368131, ['notes' => null], [
            'notes' => 'معامله اشتباه: سفارش لیمیت با سفارش مارکت در حال اجرا تطبیق خورد. برگشت داده شد (ادمین #12، 2026-11-19/20) و با '.self::NOTE.' تکمیل شد.',
        ]);
        $this->summary[] = ['trade #368131', $noted ? 'note added' : 'already noted'];
    }

    private function fixApeClawback(): void
    {
        $old   = '224.97768570';
        $audio = bcmul('9753.69590780', '0.01104', 16);            // AUDIO clawback at its recorded price
        $ape   = bcdiv(bcsub('119.735', $audio, 16), '0.0816', self::SCALE);
        $delta = bcsub($old, $ape, self::SCALE);                    // APE to give back
        $note  = self::NOTE." — برداشت APE از {$old} به {$ape} اصلاح شد (مجموع AUDIO + APE = 119.735 USDT)";

        // user #53 → exchange clawback (admin #12)
        if (! $this->edit('transactions', 210258,
            ['user_id' => self::SELLER, 'wallet_id' => self::SELLER_APE, 'amount' => "-{$old}", 'notes' => null],
            ['amount' => "-{$ape}", 'notes' => $note])) {
            $this->summary[] = ['APE clawback', 'already fixed'];

            return;
        }
        $this->edit('withdrawals', 9477, ['user_id' => self::SELLER, 'amount' => $old],
            ['amount' => $ape, 'usdt_value' => bcmul($ape, '0.0816', self::SCALE), 'remark' => $note]);
        $this->edit('transactions', 210259,
            ['user_id' => self::EXCHANGE, 'wallet_id' => self::EXCHANGE_APE, 'amount' => $old, 'notes' => null],
            ['amount' => $ape, 'notes' => $note]);
        $this->edit('deposits', 15813, ['user_id' => self::EXCHANGE, 'amount' => $old],
            ['amount' => $ape, 'usdt_value' => bcmul($ape, '0.0816', self::SCALE)]);

        $this->shiftWallet(self::SELLER_APE, $delta);
        $this->shiftSnapshots(self::SELLER_APE, 210258, $delta);
        $this->shiftSnapshots(self::EXCHANGE_APE, 210259, "-{$delta}");

        // exchange APE outflow (admin #20, 2026-11-19 14:17) carried that APE out
        $outNote = self::NOTE." — {$delta} APE از این برداشت کسر شد (به کاربر #53 برگشت)";
        $out = bcsub('224.97', $delta, self::SCALE);
        $this->edit('transactions', 210310,
            ['user_id' => self::EXCHANGE, 'wallet_id' => self::EXCHANGE_APE, 'amount' => '224.97', 'notes' => null],
            ['amount' => $out, 'notes' => $outNote]);
        $this->edit('withdrawals', 9483, ['user_id' => self::EXCHANGE, 'amount' => '224.97'],
            ['amount' => $out, 'usdt_value' => bcmul($out, '0.0811', self::SCALE), 'remark' => $outNote]);
        $this->shiftSnapshots(self::EXCHANGE_APE, 210310, $delta);

        $this->summary[] = ['APE clawback', "{$old} → {$ape} APE; {$delta} APE back to user #53; admin #20 outflow 224.97 → {$out}"];
    }

    private function fixDogeReturn(): void
    {
        $doge = '217.7';
        $note = self::NOTE.' — 217.7 DOGE برگشتی از کاربر #224 به فروشنده اصلی (کاربر #53) برگشت داده شد، نه کیف پول صرافی';

        // admin #12's transfer #224 → exchange becomes #224 → #53
        if ((int) $this->row('transactions', 210399)->user_id === self::SELLER) {
            $this->summary[] = ['DOGE return', 'already fixed'];

            return;
        }
        // The row's balance snapshot becomes user #53's DOGE balance, unchanged since the incident.
        if (DB::table('transactions')->where('wallet_id', self::SELLER_DOGE)->where('id', '>', 168079)->exists()) {
            throw new \RuntimeException('user #53 DOGE wallet has transactions after the incident; recheck the balance snapshot');
        }
        $this->edit('transactions', 210399,
            ['user_id' => self::EXCHANGE, 'wallet_id' => self::EXCHANGE_DOGE, 'amount' => $doge, 'notes' => null],
            ['user_id' => self::SELLER, 'wallet_id' => self::SELLER_DOGE, 'balance' => $this->row('wallets', self::SELLER_DOGE)->balance, 'notes' => $note]);
        $this->edit('deposits', 15847, ['user_id' => self::EXCHANGE, 'currency_symbol' => 'DOGE', 'amount' => $doge],
            ['user_id' => self::SELLER]);

        $this->shiftWallet(self::SELLER_DOGE, $doge);
        $this->shiftWallet(self::EXCHANGE_DOGE, "-{$doge}");
        $this->shiftSnapshots(self::EXCHANGE_DOGE, 210399, "-{$doge}");

        // exchange DOGE outflow (admin #25, 2026-11-20 09:15) carried that DOGE out
        $outNote = self::NOTE.' — این برداشت ۰ شد؛ DOGE آن متعلق به کاربر #53 بود';
        $this->edit('transactions', 210413,
            ['user_id' => self::EXCHANGE, 'wallet_id' => self::EXCHANGE_DOGE, 'amount' => '216.73', 'notes' => null],
            ['amount' => '0', 'notes' => $outNote]);
        $this->edit('withdrawals', 9496, ['user_id' => self::EXCHANGE, 'amount' => '216.73'],
            ['amount' => '0', 'usdt_value' => '0', 'remark' => $outNote]);
        $this->shiftWallet(self::EXCHANGE_DOGE, '216.73');
        $this->shiftSnapshots(self::EXCHANGE_DOGE, 210413, '216.73');

        $this->summary[] = ['DOGE return', '217.7 DOGE → user #53 (tx #210399, deposit #15847); admin #25 outflow 216.73 → 0'];
    }

    /**
     * Update one row after checking it still holds $expect. Returns false when it
     * already holds $set (repair applied earlier); any other state aborts.
     */
    private function edit(string $table, int $id, array $expect, array $set): bool
    {
        $row = $this->row($table, $id);
        if ($this->holds($row, $set)) {
            return false;
        }
        if (! $this->holds($row, $expect)) {
            throw new \RuntimeException("{$table} #{$id} is not in the expected state: ".json_encode($row, JSON_UNESCAPED_UNICODE));
        }

        $this->backup[] = ['table' => $table, 'id' => $id, 'before' => (array) $row, 'set' => $set];
        DB::table($table)->where('id', $id)->update($set + ['updated_at' => now()]);

        return true;
    }

    private function shiftWallet(int $walletId, string $delta): void
    {
        $wallet = $this->row('wallets', $walletId);
        $this->backup[] = ['table' => 'wallets', 'id' => $walletId, 'before' => (array) $wallet, 'delta' => $delta];
        DB::table('wallets')->where('id', $walletId)->update([
            'balance' => bcadd($wallet->balance, $delta, self::SCALE),
            'updated_at' => now(),
        ]);
    }

    /** Transactions store the wallet balance before them: move it for every later row of the wallet. */
    private function shiftSnapshots(int $walletId, int $afterId, string $delta): void
    {
        $rows = DB::table('transactions')->where('wallet_id', $walletId)->where('id', '>', $afterId)
            ->whereNotNull('balance')->lockForUpdate()->get(['id', 'balance']);

        foreach ($rows as $row) {
            $this->backup[] = ['table' => 'transactions', 'id' => $row->id, 'before' => ['balance' => $row->balance], 'delta' => $delta];
            DB::table('transactions')->where('id', $row->id)->update(['balance' => bcadd($row->balance, $delta, self::SCALE)]);
        }
    }

    private function holds(object $row, array $values): bool
    {
        foreach ($values as $column => $value) {
            $current = $row->{$column};
            if ($value === null || $current === null) {
                if ($value !== $current) {
                    return false;
                }
            } elseif (is_numeric($value) && is_numeric($current)) {
                if (bccomp((string) $current, (string) $value, self::SCALE) !== 0) {
                    return false;
                }
            } elseif ((string) $current !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    private function row(string $table, int $id): object
    {
        return DB::table($table)->where('id', $id)->lockForUpdate()->first()
            ?? throw new \RuntimeException("{$table} #{$id} not found");
    }

    /** @return array<int, array{label:string, balance:string}> */
    private function wallets(array $ids): array
    {
        return DB::table('wallets')->whereIn('id', $ids)->get()->mapWithKeys(fn ($w) => [$w->id => [
            'label' => "#{$w->id} user {$w->user_id} {$w->currency_symbol}",
            'balance' => $w->balance,
        ]])->all();
    }
}
