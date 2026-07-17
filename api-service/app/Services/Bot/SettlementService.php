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

            $this->updateWallet($userId, $execution, $costBasis, $netPnl);
            $this->writeTxns($userId, $execution, $grossRevenue, $networkFee, $effectiveExchangeFee, $spreadFee, $performanceFee, null);
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

            $this->updateWallet($userId, $execution, $costBasis, $netPnl);
            $this->writeTxns($userId, $execution, '0', '0', '0', '0', '0', $cancelFee);

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
     *   - locked_balance is released by cost_basis (same as settleFill).
     *   - balance += cost_basis + net_pnl  (the net refund the user receives).
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

            $this->updateWallet($userId, $execution, $costBasis, $netPnl);
            $this->writeTxns($userId, $execution, $grossRevenue, $networkFee, $effectiveExchangeFee, '0', $perfFee, null);
            $this->payReferral($referral, $execution, $userId);

            return $settlement;
        });
    }

    /**
     * Wallet update rules:
     *   - Release the locked cost from `locked_balance` (it was reserved at buy time).
     *   - balance += net_pnl only.  The cost_basis was never subtracted from `balance`
     *     when buying (only `locked_balance` was incremented), so adding it here would
     *     double-count it and inflate the available balance.
     *   - profit_balance += net_pnl when profitable.
     */
    private function updateWallet(int $userId, BotBuyExecution $execution, string $costBasis, string $netPnl): void
    {
        $wallet = BotWallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();

        // Release the portion of locked principal that this sell-side cost basis represents.
        $newLocked = bcsub((string) $wallet->locked_balance, $costBasis, self::SCALE);
        if (bccomp($newLocked, '0', self::SCALE) < 0) {
            $newLocked = '0';
        }

        // Only the profit/loss adjusts the total balance; the principal (cost_basis) was
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
        string $grossRevenue,
        string $networkFee,
        string $exchangeFee,
        string $spreadFee,
        string $performanceFee,
        ?string $cancelFee,
    ): void {
        $walletId = Wallet::where('user_id', $userId)
            ->where('currency_symbol', 'USDT')
            ->value('id');

        $base = [
            'user_id'              => $userId,
            'wallet_id'            => $walletId,
            'bot_order_id'         => $execution->bot_order_id,
            'bot_buy_execution_id' => $execution->id,
            'type'                 => TransactionTypeEnum::BOT,
            'status'               => TransactionStatusEnum::SUCCESS,
        ];

        if (bccomp($grossRevenue, '0', self::SCALE) > 0) {
            Transaction::create($base + [
                'amount'  => $grossRevenue,
                'subtype' => TransactionSubTypeEnum::BOT_SELL,
            ]);
        }
        foreach ([
            [$networkFee,     TransactionSubTypeEnum::BOT_NETWORK_FEE],
            [$exchangeFee,    TransactionSubTypeEnum::BOT_EXCHANGE_FEE],
            [$spreadFee,      TransactionSubTypeEnum::BOT_SPREAD_FEE],
            [$performanceFee, TransactionSubTypeEnum::BOT_PERFORMANCE_FEE],
        ] as [$amount, $subtype]) {
            if (bccomp($amount, '0', self::SCALE) > 0) {
                Transaction::create($base + ['amount' => $amount, 'subtype' => $subtype]);
            }
        }
        if ($cancelFee !== null && bccomp($cancelFee, '0', self::SCALE) > 0) {
            Transaction::create($base + [
                'amount'  => $cancelFee,
                'subtype' => TransactionSubTypeEnum::BOT_CANCEL_FEE,
            ]);
        }
    }
}
