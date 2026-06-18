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
use App\Models\Transaction;
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
     * Settle a filled sell order.
     */
    public function settleFill(
        BotSellOrder $sellOrder,
        string $filledAmount,
        string $fillPrice,
        string $networkFee = '0',
        string $exchangeFee = '0',
        string $spreadFee = '0',
    ): BotTradeSettlement {
        return DB::transaction(function () use ($sellOrder, $filledAmount, $fillPrice, $networkFee, $exchangeFee, $spreadFee) {
            $sellOrder->refresh();
            $execution = $sellOrder->botBuyExecution()->lockForUpdate()->firstOrFail();
            $userId    = $execution->botOrder->user_id;

            $grossRevenue = bcmul($filledAmount, $fillPrice, self::SCALE);
            $costBasis    = bcmul($filledAmount, (string) $execution->avg_buy_price, self::SCALE);
            $totalFees    = bcadd(bcadd($networkFee, $exchangeFee, self::SCALE), $spreadFee, self::SCALE);
            $grossPnl     = bcsub(bcsub($grossRevenue, $costBasis, self::SCALE), $totalFees, self::SCALE);

            $performanceFee = '0';
            if (bccomp($grossPnl, '0', self::SCALE) > 0) {
                $pct = (string) BotGlobalSettings::current()->performance_fee_percent;
                $performanceFee = bcdiv(bcmul($grossPnl, $pct, self::SCALE), '100', self::SCALE);
            }
            $netPnl = bcsub($grossPnl, $performanceFee, self::SCALE);

            $settlement = BotTradeSettlement::create([
                'user_id'              => $userId,
                'bot_buy_execution_id' => $execution->id,
                'bot_sell_order_id'    => $sellOrder->id,
                'gross_revenue'        => $grossRevenue,
                'cost_basis'           => $costBasis,
                'network_fee'          => $networkFee,
                'exchange_fee'         => $exchangeFee,
                'spread_fee'           => $spreadFee,
                'performance_fee'      => $performanceFee,
                'cancel_fee'           => '0',
                'net_pnl'              => $netPnl,
                'settled_at'           => now(),
            ]);

            $sellOrder->update([
                'status'    => BotSellOrder::STATUS_FILLED,
                'filled_at' => now(),
            ]);

            $this->updateWallet($userId, $execution, $costBasis, $netPnl);
            $this->writeTxns($userId, $execution, $grossRevenue, $networkFee, $exchangeFee, $spreadFee, $performanceFee, null);

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
     *   - exchange_fee   = realised fee from the market-sell (0 when disabled)
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
        string $exchangeFee,
        string $perfFeePercent,
    ): BotTradeSettlement {
        return DB::transaction(function () use ($sellOrder, $filledAmount, $fillPrice, $networkFee, $exchangeFee, $perfFeePercent) {
            $sellOrder->refresh();
            $execution = $sellOrder->botBuyExecution()->lockForUpdate()->firstOrFail();
            $userId    = $execution->botOrder->user_id;

            $grossRevenue = bcmul($filledAmount, $fillPrice, self::SCALE);
            $costBasis    = bcmul($filledAmount, (string) $execution->avg_buy_price, self::SCALE);

            $grossPnl = bcsub($grossRevenue, $costBasis, self::SCALE);
            $perfFee  = '0';
            if (bccomp($grossPnl, '0', self::SCALE) > 0) {
                $perfFee = bcdiv(bcmul($grossPnl, $perfFeePercent, self::SCALE), '100', self::SCALE);
            }

            $totalFees = bcadd(bcadd($networkFee, $exchangeFee, self::SCALE), $perfFee, self::SCALE);
            $netPnl    = bcsub($grossPnl, $totalFees, self::SCALE);

            $settlement = BotTradeSettlement::create([
                'user_id'              => $userId,
                'bot_buy_execution_id' => $execution->id,
                'bot_sell_order_id'    => $sellOrder->id,
                'gross_revenue'        => $grossRevenue,
                'cost_basis'           => $costBasis,
                'network_fee'          => $networkFee,
                'exchange_fee'         => $exchangeFee,
                'spread_fee'           => '0',
                'performance_fee'      => $perfFee,
                'cancel_fee'           => '0',
                'net_pnl'              => $netPnl,
                'settled_at'           => now(),
            ]);

            $sellOrder->update([
                'status'    => BotSellOrder::STATUS_CANCELED,
                'filled_at' => null,
            ]);

            $this->updateWallet($userId, $execution, $costBasis, $netPnl);
            $this->writeTxns($userId, $execution, $grossRevenue, $networkFee, $exchangeFee, '0', $perfFee, null);

            return $settlement;
        });
    }

    /**
     * Settle an emergency fallback liquidation. Used by OpenSellOrdersJob when
     * the normal tiered sell path cannot be opened after a successful buy
     * (e.g. resulting tier(s) below the exchange p2p minimum) so the bought
     * coin would otherwise be stranded on the omnibus account.
     *
     * The supplied $filledAmount / $fillPrice / $exchangeFee describe the
     * realized market-sell on the reference exchange. A placeholder
     * BotSellOrder with status=FILLED and target_type='fallback_liquidation'
     * is created so the trade settlement row has a foreign key to point at
     * and the dispose action shows up in normal reporting.
     */
    public function settleFallbackLiquidation(
        BotBuyExecution $execution,
        string $filledAmount,
        string $fillPrice,
        string $exchangeFee,
        ?string $exchangeOrderId = null,
    ): BotTradeSettlement {
        return DB::transaction(function () use ($execution, $filledAmount, $fillPrice, $exchangeFee, $exchangeOrderId) {
            $execution->refresh();

            $sellOrder = BotSellOrder::create([
                'bot_buy_execution_id' => $execution->id,
                'exchange_order_id'    => $exchangeOrderId,
                'target_type'          => 'fallback_liquidation',
                'target_value'         => $fillPrice,
                'share_percent'        => '100.00',
                'amount_to_sell'       => $filledAmount,
                'status'               => BotSellOrder::STATUS_FILLED,
                'filled_at'            => now(),
            ]);

            return $this->settleFill(
                sellOrder:    $sellOrder,
                filledAmount: $filledAmount,
                fillPrice:    $fillPrice,
                networkFee:   '0',
                exchangeFee:  $exchangeFee,
                spreadFee:    '0',
            );
        });
    }

    /**
     * Wallet update rules (D9):
     *   - Release the locked cost from `locked_balance` (it was reserved at buy time).
     *   - If net_pnl > 0: balance += cost_basis + net_pnl; profit_balance += net_pnl.
     *   - If net_pnl ≤ 0: balance += cost_basis + net_pnl  (i.e. cost_basis − loss).
     */
    private function updateWallet(int $userId, BotBuyExecution $execution, string $costBasis, string $netPnl): void
    {
        $wallet = BotWallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();

        // Release the portion of locked principal that this sell-side cost basis represents.
        $newLocked = bcsub((string) $wallet->locked_balance, $costBasis, self::SCALE);
        if (bccomp($newLocked, '0', self::SCALE) < 0) {
            $newLocked = '0';
        }

        $delta      = bcadd($costBasis, $netPnl, self::SCALE);
        $newBalance = bcadd((string) $wallet->balance, $delta, self::SCALE);

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
