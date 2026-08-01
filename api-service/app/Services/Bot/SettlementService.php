<?php

namespace App\Services\Bot;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotWallet;
use App\Models\ReferralCode;
use App\Models\ReferralCodeUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Computes net P/L for a sell fill (or a user-initiated cancel), persists a
 * bot_trade_settlements row + the associated `transactions` rows, updates the
 * user's bot_wallets (balance, profit_balance, locked_balance) and marks the
 * bot_sell_orders row as FILLED or CANCELED.
 *
 * All operations run inside a single DB transaction (per plan constraint).
 * Reinvest is intentionally NOT triggered in this phase (D1).
 */
class SettlementService
{
    private const SCALE = 8;

    /**
     * Allocate this execution's buy-side exchange fee to the settled amount
     * proportionally, so final PnL reflects both buy and sell exchange fees.
     */
    private function allocatedBuyExchangeFee(BotBuyExecution $execution, string $settledAmount): string
    {
        $totalBuyFee = (string) ($execution->buy_ref_exchange_fee ?? '0');
        $totalFilled = (string) ($execution->filled_amount ?? '0');

        if (
            bccomp($totalBuyFee, '0', self::SCALE) <= 0 ||
            bccomp($totalFilled, '0', self::SCALE) <= 0 ||
            bccomp($settledAmount, '0', self::SCALE) <= 0
        ) {
            return '0';
        }

        return bcdiv(
            bcmul($totalBuyFee, $settledAmount, self::SCALE),
            $totalFilled,
            self::SCALE,
        );
    }

    /**
     * Portion of the execution's originally locked capital (allocated_usdt)
     * that a settled amount represents.
     *
     * When the buy fee is paid in the base currency, filled_amount is net of
     * that fee, so cost_basis (filled_amount * avg_buy_price) is smaller than
     * allocated_usdt. Releasing only cost_basis would strand the fee component
     * in locked_balance forever; releasing the allocated share guarantees the
     * full lock is freed once every tier of the execution settles.
     */
    private function lockedReleaseFor(BotBuyExecution $execution, string $settledAmount, string $fallback): string
    {
        $allocated   = (string) ($execution->allocated_usdt ?? '0');
        $totalFilled = (string) ($execution->filled_amount ?? '0');

        if (
            bccomp($allocated, '0', self::SCALE) <= 0 ||
            bccomp($totalFilled, '0', self::SCALE) <= 0 ||
            bccomp($settledAmount, '0', self::SCALE) <= 0
        ) {
            return $fallback;
        }

        $release = bcdiv(bcmul($allocated, $settledAmount, self::SCALE), $totalFilled, self::SCALE);

        return bccomp($release, $allocated, self::SCALE) > 0 ? $allocated : $release;
    }

    /**
     * Settle a filled sell order.
     */
    public function settleFill(
        BotSellOrder $sellOrder,
        string $filledAmount,
        string $fillPrice,
        string $networkFee = '0',
        string $sellRefExchangeFee = '0',
        string $spreadFee = '0',
    ): BotTradeSettlement {
        return DB::transaction(function () use ($sellOrder, $filledAmount, $fillPrice, $networkFee, $sellRefExchangeFee, $spreadFee) {
            $sellOrder->refresh();
            $execution = $sellOrder->botBuyExecution()->lockForUpdate()->firstOrFail();
            $userId    = $execution->botOrder->user_id;

            $buyExchangeFeeShare = $this->allocatedBuyExchangeFee($execution, $filledAmount);
            $effectiveExchangeFee = bcadd($sellRefExchangeFee, $buyExchangeFeeShare, self::SCALE);

            $grossRevenue  = bcmul($filledAmount, $fillPrice, self::SCALE);
            $costBasis     = bcmul($filledAmount, (string) $execution->avg_buy_price, self::SCALE);
            $totalFees     = bcadd(bcadd($networkFee, $effectiveExchangeFee, self::SCALE), $spreadFee, self::SCALE);
            $grossPnl      = bcsub($grossRevenue, $costBasis, self::SCALE);
            $pnlAfterFees  = bcsub($grossPnl, $totalFees, self::SCALE);

            $performanceFee = '0';
            if (bccomp($pnlAfterFees, '0', self::SCALE) > 0) {
                $pct = (string) BotGlobalSettings::current()->performance_fee_percent;
                $performanceFee = bcdiv(bcmul($pnlAfterFees, $pct, self::SCALE), '100', self::SCALE);
            }
            $netPnl = bcsub($pnlAfterFees, $performanceFee, self::SCALE);

            // Referral: a slice of the exchange's performance fee is routed to the
            // settling user's introducer. This does NOT change the user's net_pnl.
            $referral = $this->resolveReferral($userId, $pnlAfterFees, $performanceFee);

            $settlement = BotTradeSettlement::create([
                'user_id'              => $userId,
                'bot_buy_execution_id' => $execution->id,
                'bot_sell_order_id'    => $sellOrder->id,
                'gross_revenue'        => $grossRevenue,
                'cost_basis'           => $costBasis,
                'network_fee'          => $networkFee,
                'exchange_fee'         => $effectiveExchangeFee,
                'spread_fee'           => $spreadFee,
                'performance_fee'      => $performanceFee,
                'referral_fee'         => $referral['fee'] ?? '0',
                'referral_user_id'     => $referral['introducer']->id ?? null,
                'cancel_fee'           => '0',
                'net_pnl'              => $netPnl,
                'settled_at'           => now(),
            ]);

            $sellOrder->update([
                'status'    => BotSellOrder::STATUS_FILLED,
                'filled_at' => now(),
            ]);

            $this->updateWallet($userId, $execution, $this->lockedReleaseFor($execution, $filledAmount, $costBasis), $netPnl);
            $this->writeTxns($userId, $execution, $networkFee, $effectiveExchangeFee, $spreadFee, $performanceFee, null);
            $this->payReferral($referral, $execution, $userId);

            return $settlement;
        });
    }

    /**
     * Settle a user-initiated cancel. The unsold portion is returned to the
     * wallet at cost_basis (no realized P/L); a cancel_fee is deducted.
     */
    public function settleCancel(
        BotSellOrder $sellOrder,
        string $cancelFee,
    ): BotTradeSettlement {
        return DB::transaction(function () use ($sellOrder, $cancelFee) {
            $sellOrder->refresh();
            $execution = $sellOrder->botBuyExecution()->lockForUpdate()->firstOrFail();
            $userId    = $execution->botOrder->user_id;

            $costBasis = bcmul((string) $sellOrder->amount_to_sell, (string) $execution->avg_buy_price, self::SCALE);
            // Net P/L on a cancel = -cancel_fee (no gross_revenue, no performance fee).
            $netPnl = bcsub('0', $cancelFee, self::SCALE);

            $settlement = BotTradeSettlement::create([
                'user_id'              => $userId,
                'bot_buy_execution_id' => $execution->id,
                'bot_sell_order_id'    => $sellOrder->id,
                'gross_revenue'        => '0',
                'cost_basis'           => $costBasis,
                'network_fee'          => '0',
                'exchange_fee'         => '0',
                'spread_fee'           => '0',
                'performance_fee'      => '0',
                'cancel_fee'           => $cancelFee,
                'net_pnl'              => $netPnl,
                'settled_at'           => now(),
            ]);

            $sellOrder->update([
                'status'    => BotSellOrder::STATUS_CANCELED,
                'filled_at' => null,
            ]);

            $this->updateWallet(
                $userId,
                $execution,
                $this->lockedReleaseFor($execution, (string) $sellOrder->amount_to_sell, $costBasis),
                $netPnl,
            );
            $this->writeTxns($userId, $execution, '0', '0', '0', '0', $cancelFee);

            return $settlement;
        });
    }

    /**
     * Settle a user-initiated cancel where the held coin is market-sold (or
     * priced at the live feed when the global "sell on exchange" flag is off).
     *
     * Differences from settleCancel():
     *   - gross_revenue  = filled_amount * fill_price (real or live-price)
    *   - exchange_fee   = market-sell fee + proportional buy-side fee share
     *   - network_fee    = withdrawal fee for the cheapest chain, in USDT
     *   - performance_fee = max(0, gross_revenue - cost_basis) * perfPct / 100
     *   - net_pnl        = gross_revenue - cost_basis - network - exchange - performance
     *   - locked_balance is released by this tier's share of allocated_usdt (same as settleFill).
     *   - balance += net_pnl (the principal was never deducted from balance at buy time).
     */
    public function settleCancelMarketSell(
        BotSellOrder $sellOrder,
        string $filledAmount,
        string $fillPrice,
        string $networkFee,
        string $sellRefExchangeFee,
        string $perfFeePercent,
    ): BotTradeSettlement {
        return DB::transaction(function () use ($sellOrder, $filledAmount, $fillPrice, $networkFee, $sellRefExchangeFee, $perfFeePercent) {
            $sellOrder->refresh();
            $execution = $sellOrder->botBuyExecution()->lockForUpdate()->firstOrFail();
            $userId    = $execution->botOrder->user_id;

            $buyExchangeFeeShare = $this->allocatedBuyExchangeFee($execution, $filledAmount);
            $effectiveExchangeFee = bcadd($sellRefExchangeFee, $buyExchangeFeeShare, self::SCALE);

            $grossRevenue = bcmul($filledAmount, $fillPrice, self::SCALE);
            $costBasis    = bcmul($filledAmount, (string) $execution->avg_buy_price, self::SCALE);

            $grossPnl     = bcsub($grossRevenue, $costBasis, self::SCALE);
            $pnlAfterFees = bcsub(bcsub($grossPnl, $networkFee, self::SCALE), $effectiveExchangeFee, self::SCALE);
            $perfFee      = '0';
            if (bccomp($pnlAfterFees, '0', self::SCALE) > 0) {
                $perfFee = bcdiv(bcmul($pnlAfterFees, $perfFeePercent, self::SCALE), '100', self::SCALE);
            }

            $netPnl = bcsub($pnlAfterFees, $perfFee, self::SCALE);

            $referral = $this->resolveReferral($userId, $pnlAfterFees, $perfFee);

            $settlement = BotTradeSettlement::create([
                'user_id'              => $userId,
                'bot_buy_execution_id' => $execution->id,
                'bot_sell_order_id'    => $sellOrder->id,
                'gross_revenue'        => $grossRevenue,
                'cost_basis'           => $costBasis,
                'network_fee'          => $networkFee,
                'exchange_fee'         => $effectiveExchangeFee,
                'spread_fee'           => '0',
                'performance_fee'      => $perfFee,
                'referral_fee'         => $referral['fee'] ?? '0',
                'referral_user_id'     => $referral['introducer']->id ?? null,
                'cancel_fee'           => '0',
                'net_pnl'              => $netPnl,
                'settled_at'           => now(),
            ]);

            $sellOrder->update([
                'status'    => BotSellOrder::STATUS_CANCELED,
                'filled_at' => null,
            ]);

            $this->updateWallet($userId, $execution, $this->lockedReleaseFor($execution, $filledAmount, $costBasis), $netPnl);
            $this->writeTxns($userId, $execution, $networkFee, $effectiveExchangeFee, '0', $perfFee, null);
            $this->payReferral($referral, $execution, $userId);

            return $settlement;
        });
    }

    /**
     * Wallet update rules:
     *   - Release the settled tier's share of the locked principal from
     *     `locked_balance` (it was reserved as allocated_usdt at buy time).
     *   - balance += net_pnl only.  The principal was never subtracted from `balance`
     *     when buying (only `locked_balance` was incremented), so adding it here would
     *     double-count it and inflate the available balance.
     *   - profit_balance += net_pnl when profitable.
     */
    private function updateWallet(int $userId, BotBuyExecution $execution, string $lockedRelease, string $netPnl): void
    {
        $wallet = BotWallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();

        // Release this tier's share of the locked principal.
        $newLocked = bcsub((string) $wallet->locked_balance, $lockedRelease, self::SCALE);
        if (bccomp($newLocked, '0', self::SCALE) < 0) {
            $newLocked = '0';
        }

        // Only the profit/loss adjusts the total balance; the principal was
        // already counted in balance since it was never deducted at buy time.
        $newBalance = bcadd((string) $wallet->balance, $netPnl, self::SCALE);

        $newProfit = (string) $wallet->profit_balance;
        if (bccomp($netPnl, '0', self::SCALE) > 0) {
            $newProfit = bcadd($newProfit, $netPnl, self::SCALE);
        }

        $wallet->update([
            'balance'        => $newBalance,
            'profit_balance' => $newProfit,
            'locked_balance' => $newLocked,
        ]);
    }

    /**
     * Determine the referral commission owed to the settling user's introducer.
     *
     * The commission is `referral_fee_percent` of the same profit base used for the
     * performance fee (pnlAfterFees) and is capped at the performance fee itself, so
     * it is always carved out of the exchange's take and never touches the user's
     * net_pnl. Returns null (no referral) when the user has no introducer, the rate
     * is zero, there is no positive profit, or the introducer has no USDT wallet.
     *
     * @return array{fee: string, introducer: User, referral_code: ReferralCode, wallet: Wallet}|null
     */
    private function resolveReferral(int $userId, string $pnlAfterFees, string $performanceFee): ?array
    {
        if (bccomp($pnlAfterFees, '0', self::SCALE) <= 0 || bccomp($performanceFee, '0', self::SCALE) <= 0) {
            return null;
        }

        $referralPct = (string) BotGlobalSettings::current()->referral_fee_percent;
        if (bccomp($referralPct, '0', self::SCALE) <= 0) {
            return null;
        }

        $user = User::query()->find($userId);
        if (! $user || ! $user->introducer_code) {
            return null;
        }

        $referralCode = ReferralCode::query()->find($user->introducer_code);
        if (! $referralCode) {
            return null;
        }

        $introducer = User::query()->find($referralCode->user_id);
        if (! $introducer) {
            return null;
        }

        $wallet = Wallet::query()
            ->where('user_id', $introducer->id)
            ->where('currency_symbol', 'USDT')
            ->lockForUpdate()
            ->first();
        if (! $wallet) {
            return null;
        }

        $referralFee = bcdiv(bcmul($pnlAfterFees, $referralPct, self::SCALE), '100', self::SCALE);
        // Never route more than the exchange's own performance-fee take on this settlement.
        if (bccomp($referralFee, $performanceFee, self::SCALE) > 0) {
            $referralFee = $performanceFee;
        }
        if (bccomp($referralFee, '0', self::SCALE) <= 0) {
            return null;
        }

        return [
            'fee'           => $referralFee,
            'introducer'    => $introducer,
            'referral_code' => $referralCode,
            'wallet'        => $wallet,
        ];
    }

    /**
     * Credit the introducer's USDT wallet with the referral commission, record the
     * payout transaction and log the referral-code usage. No exchange-wallet entry
     * is written (the commission is funded from the performance-fee share).
     *
     * @param  array{fee: string, introducer: User, referral_code: ReferralCode, wallet: Wallet}|null  $referral
     */
    private function payReferral(?array $referral, BotBuyExecution $execution, int $fromUserId): void
    {
        if ($referral === null) {
            return;
        }

        /** @var Wallet $wallet */
        $wallet = $referral['wallet'];
        /** @var User $introducer */
        $introducer = $referral['introducer'];
        /** @var ReferralCode $referralCode */
        $referralCode = $referral['referral_code'];
        $fee = $referral['fee'];

        $wallet->increment('balance', $fee);
        $wallet->refresh();

        $transaction = Transaction::create([
            'user_id'              => $introducer->id,
            'wallet_id'            => $wallet->id,
            'bot_order_id'         => $execution->bot_order_id,
            'bot_buy_execution_id' => $execution->id,
            'balance'              => $wallet->balance,
            'amount'               => $fee,
            'type'                 => TransactionTypeEnum::REFERRAL,
            'subtype'              => TransactionSubTypeEnum::BOT_REFERRAL_COMMISSION,
            'status'               => TransactionStatusEnum::SUCCESS,
            'description'          => "Bot referral commission (introducer) from bot order #{$execution->bot_order_id}",
        ]);

        ReferralCodeUsage::create([
            'referral_code_id' => $referralCode->id,
            'used_by'          => $fromUserId,
            'transaction_id'   => $transaction->id,
            'type'             => 'introducer',
            'used_at'          => now(),
        ]);
    }

    private function writeTxns(
        int $userId,
        BotBuyExecution $execution,
        string $networkFee,
        string $exchangeFee,
        string $spreadFee,
        string $performanceFee,
        ?string $cancelFee,
    ): void {
        $wallet = Wallet::where('user_id', $userId)
            ->where('currency_symbol', 'USDT')
            ->first();

        $base = [
            'user_id'              => $userId,
            'wallet_id'            => $wallet?->id,
            'bot_order_id'         => $execution->bot_order_id,
            'bot_buy_execution_id' => $execution->id,
            'balance'              => $wallet?->balance,
            'type'                 => TransactionTypeEnum::BOT,
            'status'               => TransactionStatusEnum::SUCCESS,
        ];

        foreach ([
            [$networkFee, TransactionSubTypeEnum::BOT_NETWORK_FEE],
            [$spreadFee,  TransactionSubTypeEnum::BOT_SPREAD_FEE],
        ] as [$amount, $subtype]) {
            if (bccomp($amount, '0', self::SCALE) > 0) {
                Transaction::create($base + ['amount' => $amount, 'subtype' => $subtype]);
            }
        }
        if (bccomp($exchangeFee, '0', self::SCALE) > 0) {
            $this->recordExchangeFee($execution, $exchangeFee);
        }
        if (bccomp($performanceFee, '0', self::SCALE) > 0) {
            $this->creditExchangePerformanceFee($execution, $performanceFee, $userId);
        }
        if ($cancelFee !== null && bccomp($cancelFee, '0', self::SCALE) > 0) {
            Transaction::create($base + [
                'amount'  => $cancelFee,
                'subtype' => TransactionSubTypeEnum::BOT_CANCEL_FEE,
            ]);
        }
    }

    /**
     * Ledger the ref-exchange trading fee against the Bitexroom user on the
     * wallet of the asset the fee was actually paid in (same pattern as
     * ExchangeService REF_EXCHANGE_*_FEE — negative amount, no bot-trader txn).
     */
    private function recordExchangeFee(BotBuyExecution $execution, string $exchangeFeeUsdt): void
    {
        $exchangeUserId = (int) config('bitexroom.user_id', 1);
        $feeCurrency    = strtoupper((string) ($execution->buy_ref_exchange_fee_currency ?: 'USDT'));

        // Settlement stores fees in USDT terms; convert back to fee-asset units when needed.
        $feeAmount = $exchangeFeeUsdt;
        if ($feeCurrency !== 'USDT') {
            $avg = (string) ($execution->avg_buy_price ?? '0');
            if (bccomp($avg, '0', self::SCALE) > 0) {
                $feeAmount = bcdiv($exchangeFeeUsdt, $avg, self::SCALE);
            }
        }

        $exchangeWallet = Wallet::query()->firstOrCreate(
            [
                'user_id'         => $exchangeUserId,
                'currency_symbol' => $feeCurrency,
            ],
            [
                'balance'        => '0',
                'locked_balance' => '0',
            ],
        );
        $exchangeWallet = Wallet::where('id', $exchangeWallet->id)->lockForUpdate()->firstOrFail();

        Transaction::create([
            'user_id'              => $exchangeUserId,
            'wallet_id'            => $exchangeWallet->id,
            'bot_order_id'         => $execution->bot_order_id,
            'bot_buy_execution_id' => $execution->id,
            'amount'               => bcmul($feeAmount, '-1', self::SCALE),
            'balance'              => $exchangeWallet->balance,
            'type'                 => TransactionTypeEnum::BOT,
            'subtype'              => TransactionSubTypeEnum::BOT_EXCHANGE_FEE,
            'status'               => TransactionStatusEnum::SUCCESS,
            'description'          => sprintf(
                'Exchange fee %s (%s) paid for bot order #%s',
           
                $feeCurrency,
                formatNumberTrimZeros($feeAmount),
                $execution->bot_order_id,
            ),
        ]);
    }

    /**
     * Performance fee is the exchange's take — credit the Bitexroom USDT wallet
     * and ledger the txn against config('bitexroom.user_id'), not the trader.
     */
    private function creditExchangePerformanceFee(
        BotBuyExecution $execution,
        string $performanceFee,
        int $fromUserId,
    ): void {
        $exchangeUserId = (int) config('bitexroom.user_id', 1);
        $exchangeWallet = Wallet::where('user_id', $exchangeUserId)
            ->where('currency_symbol', 'USDT')
            ->lockForUpdate()
            ->firstOrFail();

        $balanceBefore = $exchangeWallet->balance;
        $exchangeWallet->increment('balance', $performanceFee);

        Transaction::create([
            'user_id'              => $exchangeUserId,
            'wallet_id'            => $exchangeWallet->id,
            'bot_order_id'         => $execution->bot_order_id,
            'bot_buy_execution_id' => $execution->id,
            'amount'               => $performanceFee,
            'balance'              => $balanceBefore,
            'type'                 => TransactionTypeEnum::BOT,
            'subtype'              => TransactionSubTypeEnum::BOT_PERFORMANCE_FEE,
            'status'               => TransactionStatusEnum::SUCCESS,
            'description'          => "Bot performance fee from user #{$fromUserId} (bot order #{$execution->bot_order_id})",
        ]);
    }
}
