<?php

namespace App\Console\Commands;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Repairs bot transactions that SettlementService (api-service) recorded wrongly
 * before the ledger fix:
 *
 *   1. referral     — the introducer was paid but the Bitexroom USDT wallet was
 *                     never debited: add the expense txn, debit the wallet.
 *   2. network-fee  — network / cancel fees were written on the trader's main
 *                     wallet and never credited to Bitexroom: move them to the
 *                     Bitexroom USDT wallet and credit it.
 *   3. fee-split    — when the buy fee was paid in the base coin, the whole
 *                     exchange fee (including the USDT sell fee) was ledgered in
 *                     that coin: keep only the buy share in the coin and add the
 *                     sell fee as a USDT row.
 *   4. missing-fee  — admin coin-cancel / migration below-min settlements never
 *                     ledgered the tier's buy fee share, and the aggregated
 *                     market-sell fee of those runs was never ledgered.
 *
 * Settlement rows and user balances are not touched. Every pass checks what is
 * already in place, so running it again (or after the fixed code is live) only
 * picks up what is still wrong. Without --execute everything runs inside a
 * transaction that is rolled back.
 */
class FixBotTransactionLedger extends Command
{
    protected $signature = 'bot:fix-transaction-ledger
        {--execute : Commit the changes (default: dry-run that is rolled back)}';

    protected $description = 'Repair bot fee / referral transactions recorded before the ledger fix';

    private const SCALE  = 8;
    // Seconds between a settlement row and the txns it wrote. Usually the same
    // second, but a settlement waiting on a wallet lock wrote them up to ~30s
    // later; matches also require the same execution, amount and currency.
    private const WINDOW = 60;
    private const NOTE   = 'bot-ledger-fix 2026-11';

    private int $exchangeUserId;
    private object $usdtWallet;
    private string $now;

    /** @var array<string, list<array<string,mixed>>> */
    private array $backup = ['updated' => [], 'inserted' => []];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $this->line($execute
            ? '<fg=red;options=bold>Mode: EXECUTE — changes will be committed.</>'
            : '<fg=yellow;options=bold>Mode: DRY-RUN — everything is rolled back. Add --execute to commit.</>');
        $this->newLine();

        $this->exchangeUserId = (int) config('bitexroom.user_id', 1);
        $this->now            = now()->toDateTimeString();

        // Telescope would keep every one of the ~15k queries in memory.
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        DB::beginTransaction();

        try {
            $exported = DB::table('transactions')
                ->whereIn('subtype', [
                    TransactionSubTypeEnum::BOT_REFERRAL_COMMISSION->value,
                    TransactionSubTypeEnum::BOT_NETWORK_FEE->value,
                    TransactionSubTypeEnum::BOT_CANCEL_FEE->value,
                    TransactionSubTypeEnum::BOT_EXCHANGE_FEE->value,
                ])
                ->whereNotNull('journal_entry_number')
                ->count();
            if ($exported > 0) {
                throw new \RuntimeException("{$exported} bot fee/referral transactions are already in accounting (journal_entry_number set); they need reversal entries, not edits");
            }

            $this->usdtWallet = DB::table('wallets')
                ->where('user_id', $this->exchangeUserId)
                ->where('currency_symbol', 'USDT')
                ->lockForUpdate()
                ->first() ?? throw new \RuntimeException("Bitexroom USDT wallet (user #{$this->exchangeUserId}) not found");
            $usdtBefore = $this->num($this->usdtWallet->balance);

            $rows   = [];
            $rows[] = $this->fixReferral();
            $rows[] = $this->fixNetworkFees();
            $rows[] = $this->fixFeeSplit();
            $rows[] = $this->fixMissingFees();
            $rows[] = $this->fixAggregatedSellFees();

            $usdtAfter = $this->num(DB::table('wallets')->where('id', $this->usdtWallet->id)->value('balance'));

            $this->table(['Pass', 'Rows', 'Amount (USDT)', 'Notes'], $rows);
            $this->line("Bitexroom USDT wallet: {$usdtBefore} → {$usdtAfter}");
            $this->newLine();

            if ($execute) {
                $path = sprintf('bot-ledger-fix/%s.json', now()->format('Ymd_His'));
                if (! Storage::disk('local')->put($path, json_encode($this->backup + ['summary' => $rows, 'usdt_wallet' => [$usdtBefore, $usdtAfter]], JSON_PRETTY_PRINT))) {
                    throw new \RuntimeException("could not write backup {$path}");
                }
                DB::commit();
                Log::warning('bot.fix_transaction_ledger', ['summary' => $rows, 'usdt_wallet' => [$usdtBefore, $usdtAfter]]);
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

    /** 1. Debit Bitexroom for every referral commission that has no expense row. */
    private function fixReferral(): array
    {
        $subtype = TransactionSubTypeEnum::BOT_REFERRAL_COMMISSION->value;

        $commissions = DB::table('transactions')
            ->where('subtype', $subtype)
            ->where('user_id', '!=', $this->exchangeUserId)
            ->where('amount', '>', 0)
            ->orderBy('id')
            ->get();

        $expenses = DB::table('transactions')
            ->where('subtype', $subtype)
            ->where('user_id', $this->exchangeUserId)
            ->where('amount', '<', 0)
            ->get(['id', 'bot_buy_execution_id', 'amount', 'created_at'])
            ->groupBy('bot_buy_execution_id');

        $claimed = [];
        $total   = '0';
        $count   = 0;
        foreach ($commissions as $c) {
            $fee   = $this->num($c->amount);
            $match = ($expenses[$c->bot_buy_execution_id] ?? collect())->first(fn ($x) => ! isset($claimed[$x->id])
                && bccomp($this->num($x->amount), bcmul($fee, '-1', self::SCALE), self::SCALE) === 0
                && abs(Carbon::parse($x->created_at)->diffInSeconds(Carbon::parse($c->created_at))) <= self::WINDOW);
            if ($match) {
                $claimed[$match->id] = true;

                continue;
            }

            $this->insertTxn([
                'user_id'              => $this->exchangeUserId,
                'wallet_id'            => $this->usdtWallet->id,
                'bot_order_id'         => $c->bot_order_id,
                'bot_buy_execution_id' => $c->bot_buy_execution_id,
                'amount'               => bcmul($fee, '-1', self::SCALE),
                'coin_price'           => '1',
                'type'                 => TransactionTypeEnum::REFERRAL->value,
                'subtype'              => $subtype,
                'description'          => "Bot referral commission expense (introducer #{$c->user_id}) from bot order #{$c->bot_order_id}",
                'created_at'           => $c->created_at,
            ], "referral expense for txn #{$c->id}");
            $total = bcadd($total, $fee, self::SCALE);
            $count++;
        }

        $this->moveUsdtBalance(bcmul($total, '-1', self::SCALE));

        return ['1. referral expense added', $count, '-'.$total, $commissions->count().' commissions checked'];
    }

    /** 2. Move network / cancel fee rows from the trader to Bitexroom and credit Bitexroom. */
    private function fixNetworkFees(): array
    {
        $rows = DB::table('transactions')
            ->whereIn('subtype', [TransactionSubTypeEnum::BOT_NETWORK_FEE->value, TransactionSubTypeEnum::BOT_CANCEL_FEE->value])
            ->where('user_id', '!=', $this->exchangeUserId)
            ->orderBy('id')
            ->get();

        $total = '0';
        foreach ($rows as $t) {
            $label = $t->subtype === TransactionSubTypeEnum::BOT_NETWORK_FEE->value ? 'network' : 'cancel';
            $this->updateTxn($t, [
                'user_id'     => $this->exchangeUserId,
                'wallet_id'   => $this->usdtWallet->id,
                'balance'     => null,
                'description' => "Bot {$label} fee from user #{$t->user_id} (bot order #{$t->bot_order_id})",
            ], 'moved from the trader wallet to Bitexroom');
            $total = bcadd($total, $this->num($t->amount), self::SCALE);
        }

        $this->moveUsdtBalance($total);

        return ['2. network/cancel fee moved', $rows->count(), '+'.$total, ''];
    }

    /** 3. Split coin-ledgered exchange fees into the coin buy share + a USDT sell fee row. */
    private function fixFeeSplit(): array
    {
        $settlements = DB::table('bot_trade_settlements as s')
            ->join('bot_sell_orders as so', 'so.id', '=', 's.bot_sell_order_id')
            ->join('bot_buy_executions as e', 'e.id', '=', 's.bot_buy_execution_id')
            ->where('s.exchange_fee', '>', 0)
            ->where('e.avg_buy_price', '>', 0)
            ->whereNotNull('e.buy_ref_exchange_fee_currency')
            ->whereRaw("UPPER(e.buy_ref_exchange_fee_currency) NOT IN ('', 'USDT')")
            ->orderBy('s.id')
            ->get(['s.id', 's.exchange_fee', 's.created_at', 's.bot_buy_execution_id', 'so.amount_to_sell',
                'e.bot_order_id', 'e.buy_ref_exchange_fee', 'e.buy_ref_exchange_fee_currency', 'e.filled_amount', 'e.avg_buy_price']);

        $feeTxns = $this->exchangeFeeTxnsFor($settlements->pluck('bot_buy_execution_id')->unique());

        $claimed = [];
        $fixed   = 0;
        $missing = 0;
        $total   = '0';
        foreach ($settlements as $s) {
            $xf       = $this->num($s->exchange_fee);
            $avg      = $this->num($s->avg_buy_price);
            $currency = strtoupper($s->buy_ref_exchange_fee_currency);
            $sellPart = bcsub($xf, $this->buyShare($s), self::SCALE);
            if (bccomp($sellPart, '0', self::SCALE) <= 0) {
                continue; // the whole recorded fee is the buy share — already right
            }

            $match = $this->claimFeeTxn($feeTxns, $claimed, $s, $currency, bcmul(bcdiv($xf, $avg, self::SCALE), '-1', self::SCALE));
            if (! $match) {
                $missing++; // already split (or never written) — nothing to repair

                continue;
            }

            $coinAmount = bcdiv(bcsub($xf, $sellPart, self::SCALE), $avg, self::SCALE);
            $this->updateTxn($match, [
                'amount'      => bcmul($coinAmount, '-1', self::SCALE),
                'description' => sprintf('Exchange fee %s (%s) paid for bot order #%s', $currency, $this->trim($coinAmount), $s->bot_order_id),
            ], 'buy-fee share only; sell fee moved to a USDT row');
            $this->insertTxn([
                'user_id'              => $this->exchangeUserId,
                'wallet_id'            => $this->usdtWallet->id,
                'bot_order_id'         => $s->bot_order_id,
                'bot_buy_execution_id' => $s->bot_buy_execution_id,
                'amount'               => bcmul($sellPart, '-1', self::SCALE),
                'coin_price'           => '1',
                'type'                 => TransactionTypeEnum::BOT->value,
                'subtype'              => TransactionSubTypeEnum::BOT_EXCHANGE_FEE->value,
                'description'          => sprintf('Exchange fee USDT (%s) paid for bot order #%s', $this->trim($sellPart), $s->bot_order_id),
                'created_at'           => $match->created_at,
            ], "sell fee split from txn #{$match->id} (settlement #{$s->id})");
            $fixed++;
            $total = bcadd($total, $sellPart, self::SCALE);
        }

        return ['3. sell fee moved to USDT', $fixed, $total, "{$missing} already split / not found"];
    }

    /** 4a. Ledger the buy-fee share of tiers settled by an admin coin cancel / migration close. */
    private function fixMissingFees(): array
    {
        $settlements = DB::table('bot_trade_settlements as s')
            ->join('bot_sell_orders as so', 'so.id', '=', 's.bot_sell_order_id')
            ->join('bot_buy_executions as e', 'e.id', '=', 's.bot_buy_execution_id')
            ->whereIn('so.cancel_reason', ['admin_currency_cancel', 'migration_below_min'])
            ->where('e.buy_ref_exchange_fee', '>', 0)
            ->where('e.filled_amount', '>', 0)
            ->orderBy('s.id')
            ->get(['s.id', 's.created_at', 's.bot_buy_execution_id', 'so.amount_to_sell',
                'e.bot_order_id', 'e.buy_ref_exchange_fee', 'e.buy_ref_exchange_fee_currency', 'e.filled_amount', 'e.avg_buy_price']);

        $feeTxns = $this->exchangeFeeTxnsFor($settlements->pluck('bot_buy_execution_id')->unique());

        $count = 0;
        $total = '0';
        foreach ($settlements as $s) {
            $share = $this->buyShare($s);
            if (bccomp($share, '0', self::SCALE) <= 0) {
                continue;
            }

            // Same rule as SettlementService::recordExchangeFee: the buy share is
            // ledgered in the coin it was paid in, converted at avg_buy_price.
            $currency = strtoupper((string) ($s->buy_ref_exchange_fee_currency ?: 'USDT'));
            $avg      = $this->num($s->avg_buy_price);
            $inCoin   = $currency !== 'USDT' && bccomp($avg, '0', self::SCALE) > 0;
            $currency = $inCoin ? $currency : 'USDT';
            $amount   = $inCoin ? bcdiv($share, $avg, self::SCALE) : $share;
            $price    = $inCoin ? $avg : '1';

            // Written by the fixed SettlementService (or an earlier run of this command).
            $from    = Carbon::parse($s->created_at);
            $already = ($feeTxns[$s->bot_buy_execution_id] ?? collect())->contains(fn ($t) => strtoupper($t->currency_symbol) === $currency
                && bccomp($this->num($t->amount), bcmul($amount, '-1', self::SCALE), self::SCALE) === 0
                && abs(Carbon::parse($t->created_at)->diffInSeconds($from)) <= self::WINDOW);
            if ($already) {
                continue;
            }
            $walletId = $inCoin ? $this->exchangeWalletId($currency) : $this->usdtWallet->id;

            $this->insertTxn([
                'user_id'              => $this->exchangeUserId,
                'wallet_id'            => $walletId,
                'bot_order_id'         => $s->bot_order_id,
                'bot_buy_execution_id' => $s->bot_buy_execution_id,
                'amount'               => bcmul($amount, '-1', self::SCALE),
                'coin_price'           => $price,
                'type'                 => TransactionTypeEnum::BOT->value,
                'subtype'              => TransactionSubTypeEnum::BOT_EXCHANGE_FEE->value,
                'description'          => sprintf('Exchange fee %s (%s) paid for bot order #%s', $currency, $this->trim($amount), $s->bot_order_id),
                'created_at'           => $s->created_at,
            ], "buy-fee share of settlement #{$s->id}");
            $count++;
            $total = bcadd($total, $share, self::SCALE);
        }

        return ['4a. coin-cancel buy fee added', $count, $total, $settlements->count().' settlements checked'];
    }

    /** 4b. Ledger the one market-sell fee of each coin cancellation / migration close. */
    private function fixAggregatedSellFees(): array
    {
        $runs = collect();

        DB::table('bot_currency_cancellations')->where('sell_exchange_fee', '>', 0)->orderBy('id')->get()
            ->each(fn ($c) => $runs->push([
                'fee'  => $this->num($c->sell_exchange_fee),
                'at'   => $c->finished_at ?? $c->updated_at,
                'desc' => "Exchange fee USDT paid for bot coin cancellation #{$c->id} market sell",
            ]));

        DB::table('bot_sell_migrations')->whereNotNull('details')->orderBy('id')->get(['id', 'details', 'finished_at', 'updated_at'])
            ->each(function ($m) use ($runs) {
                $fee = json_decode((string) $m->details, true)['close']['exchange_fee'] ?? null;
                if ($fee !== null && bccomp($this->num($fee), '0', self::SCALE) > 0) {
                    $runs->push([
                        'fee'  => $this->num($fee),
                        'at'   => $m->finished_at ?? $m->updated_at,
                        'desc' => "Exchange fee USDT paid for bot sell migration #{$m->id} close market sell",
                    ]);
                }
            });

        $count = 0;
        $total = '0';
        foreach ($runs as $run) {
            if (DB::table('transactions')->where('subtype', TransactionSubTypeEnum::BOT_EXCHANGE_FEE->value)->where('description', $run['desc'])->exists()) {
                continue;
            }
            $this->insertTxn([
                'user_id'     => $this->exchangeUserId,
                'wallet_id'   => $this->usdtWallet->id,
                'amount'      => bcmul($run['fee'], '-1', self::SCALE),
                'coin_price'  => '1',
                'type'        => TransactionTypeEnum::BOT->value,
                'subtype'     => TransactionSubTypeEnum::BOT_EXCHANGE_FEE->value,
                'description' => $run['desc'],
                'created_at'  => $run['at'],
            ], 'aggregated market-sell fee');
            $count++;
            $total = bcadd($total, $run['fee'], self::SCALE);
        }

        return ['4b. market-sell fee added', $count, $total, $runs->count().' runs checked'];
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /** Same formula as SettlementService::allocatedBuyExchangeFee (USDT terms). */
    private function buyShare(object $s): string
    {
        $fee    = $this->num($s->buy_ref_exchange_fee);
        $filled = $this->num($s->filled_amount);
        $amount = $this->num($s->amount_to_sell);
        if (bccomp($fee, '0', self::SCALE) <= 0 || bccomp($filled, '0', self::SCALE) <= 0 || bccomp($amount, '0', self::SCALE) <= 0) {
            return '0';
        }

        return bcdiv(bcmul($fee, $amount, self::SCALE), $filled, self::SCALE);
    }

    /** @return Collection<int|string, Collection<int, object>> Bitexroom BOT_EXCHANGE_FEE txns by execution id */
    private function exchangeFeeTxnsFor(Collection $executionIds): Collection
    {
        if ($executionIds->isEmpty()) {
            return collect();
        }

        return $executionIds->chunk(1000)->flatMap(fn ($ids) => DB::table('transactions as t')
            ->join('wallets as w', 'w.id', '=', 't.wallet_id')
            ->where('t.subtype', TransactionSubTypeEnum::BOT_EXCHANGE_FEE->value)
            ->where('t.user_id', $this->exchangeUserId)
            ->whereIn('t.bot_buy_execution_id', $ids->values())
            ->orderBy('t.created_at')
            ->orderBy('t.id')
            ->get(['t.*', 'w.currency_symbol']))
            ->groupBy('bot_buy_execution_id');
    }

    private function claimFeeTxn(Collection $feeTxns, array &$claimed, object $s, string $currency, string $amount): ?object
    {
        $from  = Carbon::parse($s->created_at);
        $match = ($feeTxns[$s->bot_buy_execution_id] ?? collect())->first(function ($t) use ($claimed, $currency, $amount, $from) {
            $at = Carbon::parse($t->created_at);

            return ! isset($claimed[$t->id])
                && strtoupper($t->currency_symbol) === $currency
                && bccomp($this->num($t->amount), $amount, self::SCALE) === 0
                && $at->gte($from) && abs($at->diffInSeconds($from)) <= self::WINDOW;
        });
        if ($match) {
            $claimed[$match->id] = true;
        }

        return $match;
    }

    private function exchangeWalletId(string $currency): int
    {
        $id = DB::table('wallets')->where('user_id', $this->exchangeUserId)->where('currency_symbol', $currency)->value('id');

        return $id ?? DB::table('wallets')->insertGetId([
            'user_id'         => $this->exchangeUserId,
            'currency_symbol' => $currency,
            'balance'         => '0',
            'locked_balance'  => '0',
            'created_at'      => $this->now,
            'updated_at'      => $this->now,
        ]);
    }

    private function insertTxn(array $row, string $why): void
    {
        $row += [
            'balance'    => null,
            'status'     => TransactionStatusEnum::SUCCESS->value,
            'notes'      => self::NOTE.': '.$why,
            'updated_at' => $this->now,
        ];
        $id = DB::table('transactions')->insertGetId($row);
        $this->backup['inserted'][] = ['id' => $id] + $row;
    }

    private function updateTxn(object $txn, array $changes, string $why): void
    {
        $changes += ['notes' => self::NOTE.': '.$why, 'updated_at' => $this->now];
        DB::table('transactions')->where('id', $txn->id)->update($changes);
        $this->backup['updated'][] = ['before' => array_intersect_key((array) $txn, $changes + ['id' => 1]), 'after' => $changes];
    }

    private function moveUsdtBalance(string $delta): void
    {
        if (bccomp($delta, '0', self::SCALE) === 0) {
            return;
        }
        $balance = $this->num(DB::table('wallets')->where('id', $this->usdtWallet->id)->value('balance'));
        DB::table('wallets')->where('id', $this->usdtWallet->id)->update([
            'balance'    => bcadd($balance, $delta, self::SCALE),
            'updated_at' => $this->now,
        ]);
    }

    private function trim(string $n): string
    {
        return str_contains($n, '.') ? rtrim(rtrim($n, '0'), '.') : $n;
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
