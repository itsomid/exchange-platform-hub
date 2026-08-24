<?php

namespace App\Console\Commands\Bot;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotWallet;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Bot\BotOrderCancelService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unwinds bot orders that were opened against money the wallet did not hold.
 *
 * Until the wallet row lock was added to BotBuyOrchestrator, the free balance
 * was read outside the transaction that wrote the resulting lock. Two sell
 * settlements completing at once on separate queue workers therefore each
 * allocated the other's freed principal, so a user could end up with two orders
 * created in the same second whose locks together exceed the wallet balance
 * (negative withdrawable). The coins those phantom orders bought are real —
 * they were funded from the exchange's omnibus account — so repairing the
 * ledger alone is not enough; the position has to be liquidated too.
 *
 * Detection: orders sharing (user_id, created_at). Every concurrent reader saw
 * a strictly larger free balance as the settlements ahead of it committed, so
 * the order with the LARGEST total_amount_usdt is the one that observed the
 * most complete picture and is kept. The rest double-spent and are phantom.
 *
 * Repair, per phantom order:
 *   1. BotOrderCancelService::cancel() — cancels the resting limit sells,
 *      market-sells the coin, settles each tier and releases the locked
 *      principal. This is the same path an admin cancel takes.
 *   2. The net P/L that cancel credited to the user is clawed back into the
 *      exchange USDT wallet. The principal at risk was the exchange's, not the
 *      user's, so the user's bot balance must come out of this unchanged.
 *
 * Phantom orders whose tiers have already sold are only reported, never
 * unwound: their principal is long since released and retroactively reversing
 * settled trades would be guesswork.
 */
class ReconcilePhantomOrdersCommand extends Command
{
    private const SCALE = 8;

    protected $signature = 'bot:reconcile-phantom-orders
        {--dry-run : Report what would be unwound without changing anything}
        {--user= : Restrict to a single user id}
        {--order= : Comma-separated bot_order ids to unwind, bypassing detection}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Find and unwind bot orders created against a double-counted free balance';

    public function handle(BotOrderCancelService $cancelService): int
    {
        $phantoms = $this->option('order')
            ? $this->explicitOrders()
            : $this->detect($this->option('user') ? (int) $this->option('user') : null);

        if ($phantoms->isEmpty()) {
            $this->info('No phantom bot orders found.');
            return self::SUCCESS;
        }

        [$unwindable, $alreadySettled] = $phantoms->partition(
            fn (BotOrder $order) => $this->hasOpenTiers($order),
        );

        $this->report($unwindable, $alreadySettled);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('Dry run — nothing was changed.');
            return self::SUCCESS;
        }

        if ($unwindable->isEmpty()) {
            $this->warn('Nothing can be unwound automatically; see the manual-review list above.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            sprintf('Market-sell and unwind %d phantom order(s)?', $unwindable->count()),
        )) {
            $this->comment('Aborted.');
            return self::SUCCESS;
        }

        foreach ($unwindable as $order) {
            $this->unwind($order, $cancelService);
        }

        return self::SUCCESS;
    }

    /**
     * Phantom orders inferred from same-second creation groups.
     *
     * @return Collection<int, BotOrder>
     */
    private function detect(?int $userId): Collection
    {
        $groups = BotOrder::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->select('user_id', 'created_at')
            ->groupBy('user_id', 'created_at')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $phantoms = collect();

        foreach ($groups as $group) {
            $orders = BotOrder::query()
                ->where('user_id', $group->user_id)
                ->where('created_at', $group->created_at)
                ->orderByDesc('total_amount_usdt')
                ->get();

            // The largest free balance belongs to the reader that saw every
            // settlement in the batch; it is the order that would have existed
            // under correct serialisation. Everything behind it is a duplicate.
            $phantoms = $phantoms->concat(
                $orders->skip(1)->reject(fn (BotOrder $o) => $o->status === 'CANCELED'),
            );
        }

        return $phantoms->values();
    }

    /**
     * @return Collection<int, BotOrder>
     */
    private function explicitOrders(): Collection
    {
        $ids = collect(explode(',', (string) $this->option('order')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->all();

        return BotOrder::query()->whereIn('id', $ids)->get();
    }

    private function hasOpenTiers(BotOrder $order): bool
    {
        return BotSellOrder::query()
            ->whereIn('bot_buy_execution_id', $order->buyExecutions()->select('id'))
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->exists();
    }

    /**
     * @param Collection<int, BotOrder> $unwindable
     * @param Collection<int, BotOrder> $alreadySettled
     */
    private function report(Collection $unwindable, Collection $alreadySettled): void
    {
        $rows = [];

        foreach ($unwindable->groupBy('user_id') as $userId => $orders) {
            $wallet = BotWallet::where('user_id', $userId)->first();
            if (! $wallet) {
                continue;
            }

            $release = '0';
            foreach ($orders as $order) {
                $release = bcadd($release, $this->stillLocked($order), self::SCALE);
            }

            $lockedAfter = bcsub((string) $wallet->locked_balance, $release, self::SCALE);

            $rows[] = [
                $userId,
                $orders->pluck('id')->implode(', '),
                (string) $wallet->balance,
                (string) $wallet->locked_balance,
                bcsub((string) $wallet->balance, (string) $wallet->locked_balance, self::SCALE),
                $release,
                bcsub((string) $wallet->balance, $lockedAfter, self::SCALE),
            ];
        }

        $this->info('Phantom orders that can be unwound:');
        $this->table(
            ['user', 'orders', 'balance', 'locked', 'withdrawable', 'to release', 'withdrawable after'],
            $rows,
        );

        if ($alreadySettled->isNotEmpty()) {
            $this->newLine();
            $this->warn('Phantom orders whose tiers already sold — manual review, not unwound:');
            $this->table(
                ['user', 'order', 'status', 'allocated', 'created_at'],
                $alreadySettled->map(fn (BotOrder $o) => [
                    $o->user_id,
                    $o->id,
                    $o->status,
                    (string) $o->total_amount_usdt,
                    (string) $o->created_at,
                ])->all(),
            );
        }
    }

    /**
     * USDT this order still holds in the wallet's locked_balance: the full
     * allocation while a buy is in flight, otherwise the share of it backing
     * sell tiers that are still OPEN. Mirrors SettlementService::lockedReleaseFor().
     */
    private function stillLocked(BotOrder $order): string
    {
        $total = '0';

        foreach ($order->buyExecutions()->with('sellOrders')->get() as $execution) {
            $allocated = (string) $execution->allocated_usdt;

            if (in_array($execution->status, [BotBuyExecution::STATUS_PENDING, BotBuyExecution::STATUS_BUYING], true)) {
                $total = bcadd($total, $allocated, self::SCALE);
                continue;
            }

            if ($execution->status !== BotBuyExecution::STATUS_BOUGHT) {
                continue;
            }

            $filled = (string) $execution->filled_amount;
            if (bccomp($filled, '0', self::SCALE) <= 0) {
                $total = bcadd($total, $allocated, self::SCALE);
                continue;
            }

            $open = '0';
            foreach ($execution->sellOrders as $sellOrder) {
                if ($sellOrder->status === BotSellOrder::STATUS_OPEN) {
                    $open = bcadd($open, (string) $sellOrder->amount_to_sell, self::SCALE);
                }
            }

            $total = bcadd($total, bcdiv(bcmul($allocated, $open, self::SCALE), $filled, self::SCALE), self::SCALE);
        }

        return $total;
    }

    private function unwind(BotOrder $order, BotOrderCancelService $cancelService): void
    {
        $executionIds = $order->buyExecutions()->pluck('id');
        $lastSettlementId = (int) BotTradeSettlement::whereIn('bot_buy_execution_id', $executionIds)->max('id');

        $this->line("Unwinding order #{$order->id} (user {$order->user_id})…");

        $result = $cancelService->cancel($order, BotOrder::CANCEL_SOURCE_ADMIN);

        $netPnl = '0';
        $settled = BotTradeSettlement::whereIn('bot_buy_execution_id', $executionIds)
            ->where('id', '>', $lastSettlementId)
            ->pluck('net_pnl');
        foreach ($settled as $value) {
            $netPnl = bcadd($netPnl, (string) $value, self::SCALE);
        }

        $this->clawBack($order, $netPnl);

        $this->info(sprintf(
            '  order #%d: %d tier(s) sold, fees %s, net P/L %s routed to the exchange wallet',
            $order->id,
            $result['canceled'],
            $result['total_fee'],
            $netPnl,
        ));
    }

    /**
     * The cancel credited net P/L to the user's bot wallet. That position was
     * funded by the exchange, so move the P/L across and leave the user's
     * balance exactly where it started — only the lock should have changed.
     */
    private function clawBack(BotOrder $order, string $netPnl): void
    {
        if (bccomp($netPnl, '0', self::SCALE) === 0) {
            return;
        }

        DB::transaction(function () use ($order, $netPnl) {
            $botWallet = BotWallet::where('user_id', $order->user_id)->lockForUpdate()->firstOrFail();

            $newProfit = (string) $botWallet->profit_balance;
            if (bccomp($netPnl, '0', self::SCALE) > 0) {
                $newProfit = bcsub($newProfit, $netPnl, self::SCALE);
                if (bccomp($newProfit, '0', self::SCALE) < 0) {
                    $newProfit = '0';
                }
            }

            $botWallet->update([
                'balance'        => bcsub((string) $botWallet->balance, $netPnl, self::SCALE),
                'profit_balance' => $newProfit,
            ]);

            $exchangeUserId = (int) config('bitexroom.user_id', 1);
            $exchangeWallet = Wallet::query()
                ->where('user_id', $exchangeUserId)
                ->where('currency_symbol', 'USDT')
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (string) $exchangeWallet->balance;
            $exchangeWallet->update(['balance' => bcadd($balanceBefore, $netPnl, self::SCALE)]);

            Transaction::create([
                'user_id'      => $exchangeUserId,
                'wallet_id'    => $exchangeWallet->id,
                'bot_order_id' => $order->id,
                'amount'       => $netPnl,
                'balance'      => $balanceBefore,
                'type'         => TransactionTypeEnum::BOT,
                'subtype'      => TransactionSubTypeEnum::MANUAL_ADMIN,
                'status'       => TransactionStatusEnum::SUCCESS,
                'description'  => "P/L of unwound phantom bot order #{$order->id} (user #{$order->user_id})",
            ]);

            Log::channel('smart-bot')->info('bot.phantom_order.unwound', [
                'bot_order_id' => $order->id,
                'user_id'      => $order->user_id,
                'net_pnl'      => $netPnl,
            ]);
        });
    }
}
