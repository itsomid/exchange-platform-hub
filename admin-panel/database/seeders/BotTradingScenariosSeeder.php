<?php

namespace Database\Seeders;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\ExchangePrice;
use App\Models\Market;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds end-to-end auto-trade lifecycle data for 5 fixed users so the upcoming
 * P7 (reports) phase has rich, realistic data to render. Covers every major
 * lifecycle branch:
 *
 *   - user 2: fully closed, profitable cycle + user later withdrew profit
 *   - user 3: just bought, sells OPEN (waiting for first fill)
 *   - user 4: one sell forced at a loss + remaining sells OPEN
 *   - user 5: bot toggled off → all sells canceled (cancel_fee charged)
 *   - user 6: multi-batch — SKIPPED + FAILED + partial-fill + recent buy
 *
 * Wallet bookkeeping matches the invariant verified by SettlementServiceTest:
 *   balance  = deposit - locked + Σ(settled_net_pnl) - withdrawn
 *   locked   = Σ(cost_basis of OPEN sells) + Σ(allocated of PENDING execs)
 *   profit   = Σ(net_pnl) over settlements with net_pnl > 0
 *
 * Idempotent: wipes existing bot data for these user IDs before inserting.
 *
 * Depends on BotSignalsSeeder (signals + live exchange prices must exist).
 */
class BotTradingScenariosSeeder extends Seeder
{
    private const USER_IDS = [2, 3, 4, 5, 6];

    /** Coin symbols used across scenarios — must have a USDT market + exchange_prices row. */
    private const COIN_POOL = ['BTC', 'ETH', 'SOL', 'BNB', 'XRP', 'DOGE', 'ADA', 'TRX'];

    /** Cached current price per symbol (USDT). */
    private array $priceCache = [];

    public function run(): void
    {
        $this->call(BotSignalsSeeder::class);

        $existingUsers = User::whereIn('id', self::USER_IDS)->pluck('id')->all();
        if (count($existingUsers) === 0) {
            $this->command?->warn('BotTradingScenariosSeeder: none of users '.implode(',', self::USER_IDS).' exist.');
            return;
        }

        $this->wipe($existingUsers);

        $scenarios = $this->scenarios();

        foreach ($scenarios as $userId => $cfg) {
            if (! in_array($userId, $existingUsers, true)) {
                continue;
            }
            $this->seedScenario($userId, $cfg);
            $this->command?->info("  • user {$userId}: {$cfg['label']}");
        }
    }

    // ───────────────────────── Scenario definitions ─────────────────────────

    private function scenarios(): array
    {
        return [
            // ─── User 2 ────────────────────────────────────────────────────
            2 => [
                'label'              => 'فروش کامل با سود + خروج سود',
                'auto_trade_enabled' => true,
                'deposit'            => 600,
                'withdrawn'          => 400,   // user pulled profit + part of principal back to main wallet
                'orders' => [[
                    'days_ago'     => 25,
                    'triggered_by' => 'TRANSFER_IN',
                    'status'       => 'FILLED',
                    'completed'    => 20,
                    'executions'   => [
                        $this->exec('BTC', 240, 'BOUGHT', 0.92, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 18],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 14],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'filled_profit', 'days_ago' => 9],
                        ]),
                        $this->exec('ETH', 200, 'BOUGHT', 0.90, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 17],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 12],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'filled_profit', 'days_ago' => 7],
                        ]),
                        $this->exec('SOL', 160, 'BOUGHT', 0.88, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 16],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 10],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'filled_profit', 'days_ago' => 5],
                        ]),
                    ],
                ]],
            ],

            // ─── User 3 ────────────────────────────────────────────────────
            3 => [
                'label'              => 'تازه خرید انجام شده، در انتظار فروش',
                'auto_trade_enabled' => true,
                'deposit'            => 500,
                'withdrawn'          => 0,
                'orders' => [[
                    'days_ago'     => 2,
                    'triggered_by' => 'TOGGLE_ON',
                    'status'       => 'FILLED',
                    'completed'    => 2,
                    'executions'   => [
                        $this->exec('BTC', 200, 'BOUGHT', 0.99, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'open'],
                        ]),
                        $this->exec('ETH', 180, 'BOUGHT', 0.99, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'open'],
                        ]),
                        $this->exec('XRP', 120, 'BOUGHT', 1.00, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'open'],
                        ]),
                    ],
                ]],
            ],

            // ─── User 4 ────────────────────────────────────────────────────
            4 => [
                'label'              => 'خرید + یک فروش با ضرر، بقیه فعال',
                'auto_trade_enabled' => true,
                'deposit'            => 300,
                'withdrawn'          => 0,
                'orders' => [[
                    'days_ago'     => 12,
                    'triggered_by' => 'MANUAL',
                    'status'       => 'FILLED',
                    'completed'    => 12,
                    'executions'   => [
                        // SOL bought, then admin forced first tier sell at a loss
                        $this->exec('SOL', 180, 'BOUGHT', 1.08, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'filled_loss', 'days_ago' => 4, 'sell_price_factor' => 0.88],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'open'],
                        ]),
                        $this->exec('DOGE', 120, 'BOUGHT', 1.05, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'open'],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'open'],
                        ]),
                    ],
                ]],
            ],

            // ─── User 5 ────────────────────────────────────────────────────
            5 => [
                'label'              => 'بات خاموش شده، تمام سفارش‌های فروش کنسل',
                'auto_trade_enabled' => false,
                'deposit'            => 250,
                'withdrawn'          => 0,
                'orders' => [[
                    'days_ago'     => 8,
                    'triggered_by' => 'TRANSFER_IN',
                    'status'       => 'FILLED',
                    'completed'    => 8,
                    'executions'   => [
                        $this->exec('BNB', 150, 'BOUGHT', 0.96, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'canceled', 'days_ago' => 1, 'cancel_fee' => 0.5],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'canceled', 'days_ago' => 1, 'cancel_fee' => 0.5],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'canceled', 'days_ago' => 1, 'cancel_fee' => 0.6],
                        ]),
                        $this->exec('ADA', 100, 'BOUGHT', 1.02, [
                            ['trigger' => 5,  'share' => 30, 'outcome' => 'canceled', 'days_ago' => 1, 'cancel_fee' => 0.4],
                            ['trigger' => 10, 'share' => 30, 'outcome' => 'canceled', 'days_ago' => 1, 'cancel_fee' => 0.4],
                            ['trigger' => 20, 'share' => 40, 'outcome' => 'canceled', 'days_ago' => 1, 'cancel_fee' => 0.5],
                        ]),
                    ],
                ]],
            ],

            // ─── User 6 ────────────────────────────────────────────────────
            6 => [
                'label'              => 'دو بَچ خرید — شامل SKIPPED و FAILED و فروش جزئی',
                'auto_trade_enabled' => true,
                'deposit'            => 900,
                'withdrawn'          => 0,
                'orders' => [
                    [
                        'days_ago'     => 18,
                        'triggered_by' => 'TRANSFER_IN',
                        'status'       => 'PARTIALLY_FILLED',
                        'completed'    => 17,
                        'executions'   => [
                            // SKIPPED: would-have-received was below p2p_min
                            $this->exec('XRP', 4, 'SKIPPED', null, [], 'below p2p_min_order_value'),
                            // FAILED: exchange rejected the buy
                            $this->exec('TRX', 70, 'FAILED', null, [], 'coinex.buy.rejected: insufficient liquidity'),
                            // BOUGHT + sold all at profit (closed)
                            $this->exec('BTC', 350, 'BOUGHT', 0.91, [
                                ['trigger' => 5,  'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 12],
                                ['trigger' => 10, 'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 8],
                                ['trigger' => 20, 'share' => 40, 'outcome' => 'filled_profit', 'days_ago' => 4],
                            ]),
                            // BOUGHT + partial fill (one tier filled, two still open)
                            $this->exec('ETH', 250, 'BOUGHT', 0.95, [
                                ['trigger' => 5,  'share' => 30, 'outcome' => 'filled_profit', 'days_ago' => 6],
                                ['trigger' => 10, 'share' => 30, 'outcome' => 'open'],
                                ['trigger' => 20, 'share' => 40, 'outcome' => 'open'],
                            ]),
                        ],
                    ],
                    [
                        'days_ago'     => 3,
                        'triggered_by' => 'TOGGLE_ON',
                        'status'       => 'FILLED',
                        'completed'    => 3,
                        'executions'   => [
                            $this->exec('SOL', 230, 'BOUGHT', 0.98, [
                                ['trigger' => 5,  'share' => 30, 'outcome' => 'open'],
                                ['trigger' => 10, 'share' => 30, 'outcome' => 'open'],
                                ['trigger' => 20, 'share' => 40, 'outcome' => 'open'],
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function exec(
        string $symbol,
        float $allocated,
        string $status,
        ?float $avgBuyFactor,
        array $sells,
        ?string $failureReason = null,
    ): array {
        return [
            'symbol'           => $symbol,
            'allocated'        => $allocated,
            'status'           => $status,
            'avg_buy_factor'   => $avgBuyFactor,   // multiplied with current_price to get avg_buy_price
            'sells'            => $sells,
            'failure_reason'   => $failureReason,
        ];
    }

    // ────────────────────────── Persistence layer ──────────────────────────

    private function seedScenario(int $userId, array $cfg): void
    {
        DB::transaction(function () use ($userId, $cfg) {
            BotUserSettings::updateOrCreate(
                ['user_id' => $userId],
                [
                    'auto_trade_enabled' => $cfg['auto_trade_enabled'],
                    'reinvest_enabled'   => false,
                    'terms_accepted_at'  => now()->subDays(60),
                ],
            );

            $wallet = BotWallet::updateOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'principal_balance' => 0, 'profit_balance' => 0, 'locked_balance' => 0],
            );

            $totals = ['locked' => 0.0, 'settled_net_pnl' => 0.0, 'profit' => 0.0];

            foreach ($cfg['orders'] as $orderCfg) {
                $createdAt = now()->subDays($orderCfg['days_ago']);

                $orderAllocSum = 0.0;
                foreach ($orderCfg['executions'] as $execCfg) {
                    if (in_array($execCfg['status'], ['BOUGHT', 'PENDING', 'BUYING', 'FAILED'], true)) {
                        $orderAllocSum += $execCfg['allocated'];
                    }
                }

                $order = BotOrder::create([
                    'user_id'           => $userId,
                    'batch_uuid'        => (string) Str::uuid(),
                    'total_amount_usdt' => $orderAllocSum,
                    'alpha_snapshot'    => 0.15,
                    'status'            => $orderCfg['status'],
                    'triggered_by'      => $orderCfg['triggered_by'],
                    'completed_at'      => isset($orderCfg['completed']) ? now()->subDays($orderCfg['completed']) : null,
                    'created_at'        => $createdAt,
                ]);

                foreach ($orderCfg['executions'] as $execCfg) {
                    $this->seedExecution($userId, $order->id, $createdAt, $execCfg, $totals);
                }
            }

            $deposit   = (float) $cfg['deposit'];
            $withdrawn = (float) ($cfg['withdrawn'] ?? 0);
            $balance   = $deposit - $totals['locked'] + $totals['settled_net_pnl'] - $withdrawn;

            $wallet->update([
                'balance'           => round($balance, 8),
                'profit_balance'    => round($totals['profit'], 8),
                'locked_balance'    => round($totals['locked'], 8),
                'principal_balance' => 0,
            ]);
        });
    }

    private function seedExecution(int $userId, int $orderId, $createdAt, array $execCfg, array &$totals): void
    {
        $currency = Currency::where('symbol', $execCfg['symbol'])->first();
        if (! $currency) {
            return;
        }
        $signal = BotSignal::where('currency_id', $currency->id)->first();
        $price  = $this->currentPrice($execCfg['symbol']);
        if ($price === null) {
            return;
        }

        $allocated   = (float) $execCfg['allocated'];
        $avgBuyPrice = $execCfg['avg_buy_factor'] !== null
            ? round($price * $execCfg['avg_buy_factor'], 8)
            : null;
        $filled      = $avgBuyPrice ? round($allocated / $avgBuyPrice, 8) : 0.0;
        $exchangeFee = $avgBuyPrice ? round($allocated * 0.001, 8) : 0.0;

        $snapshot = $signal ? [
            'floor_price'                   => (string) $signal->floor_price,
            'ceiling_price'                 => (string) $signal->ceiling_price,
            'current_price'                 => (string) $price,
            'priority'                      => (int) $signal->priority,
            'max_allocation_percent'        => (string) $signal->max_allocation_percent,
            'sell_orders_count'             => (int) $signal->sell_orders_count,
            'weight_normalized'             => '0.50',
            'effective_p2p_min_order_value' => (string) $signal->effective_p2p_min_order_value,
        ] : ['current_price' => (string) $price];

        $execution = BotBuyExecution::create([
            'bot_order_id'               => $orderId,
            'currency_id'                => $currency->id,
            'signal_snapshot'            => $snapshot,
            'original_sell_orders_count' => $signal?->sell_orders_count,
            'allocated_usdt'             => $allocated,
            'filled_amount'              => $filled,
            'avg_buy_price'              => $avgBuyPrice,
            'exchange_fee'               => $exchangeFee,
            'network_fee'                => 0,
            'status'                     => $execCfg['status'],
            'failure_reason'             => $execCfg['failure_reason'] ?? null,
            'created_at'                 => $createdAt,
        ]);

        if ($execCfg['status'] !== 'BOUGHT') {
            return;
        }

        foreach ($execCfg['sells'] as $sellCfg) {
            $this->seedSell($userId, $execution, $avgBuyPrice, $filled, $createdAt, $sellCfg, $totals);
        }
    }

    private function seedSell(
        int $userId,
        BotBuyExecution $execution,
        float $avgBuyPrice,
        float $filled,
        $createdAt,
        array $sellCfg,
        array &$totals,
    ): void {
        $share        = (float) $sellCfg['share'];
        $trigger      = (float) $sellCfg['trigger'];
        $amountToSell = round($filled * $share / 100, 8);
        $costBasis    = round($amountToSell * $avgBuyPrice, 8);
        $outcome      = $sellCfg['outcome'];

        $sell = BotSellOrder::create([
            'bot_buy_execution_id' => $execution->id,
            'target_type'          => 'percent',
            'target_value'         => $trigger,
            'share_percent'        => $share,
            'amount_to_sell'       => $amountToSell,
            'status'               => match ($outcome) {
                'open'                              => 'OPEN',
                'filled_profit', 'filled_loss'      => 'FILLED',
                'canceled'                          => 'CANCELED',
                default                             => 'OPEN',
            },
            'filled_at'  => in_array($outcome, ['filled_profit', 'filled_loss'], true)
                ? now()->subDays($sellCfg['days_ago'] ?? 1) : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        switch ($outcome) {
            case 'open':
                $totals['locked'] += $costBasis;
                break;

            case 'filled_profit':
                $sellPrice = round($avgBuyPrice * (1 + $trigger / 100), 8);
                $this->settle($userId, $execution, $sell, $amountToSell, $costBasis, $sellPrice, $sellCfg, $totals);
                break;

            case 'filled_loss':
                $factor    = (float) ($sellCfg['sell_price_factor'] ?? 0.9);
                $sellPrice = round($avgBuyPrice * $factor, 8);
                $this->settle($userId, $execution, $sell, $amountToSell, $costBasis, $sellPrice, $sellCfg, $totals);
                break;

            case 'canceled':
                $cancelFee = (float) ($sellCfg['cancel_fee'] ?? 0.5);
                BotTradeSettlement::create([
                    'user_id'              => $userId,
                    'bot_buy_execution_id' => $execution->id,
                    'bot_sell_order_id'    => $sell->id,
                    'gross_revenue'        => 0,
                    'cost_basis'           => $costBasis,
                    'network_fee'          => 0,
                    'exchange_fee'         => 0,
                    'spread_fee'           => 0,
                    'performance_fee'      => 0,
                    'cancel_fee'           => $cancelFee,
                    'net_pnl'              => -$cancelFee,
                    'settled_at'           => now()->subDays($sellCfg['days_ago'] ?? 1),
                ]);
                $totals['settled_net_pnl'] -= $cancelFee;
                break;
        }
    }

    private function settle(
        int $userId,
        BotBuyExecution $execution,
        BotSellOrder $sell,
        float $amountToSell,
        float $costBasis,
        float $sellPrice,
        array $sellCfg,
        array &$totals,
    ): void {
        $gross        = round($amountToSell * $sellPrice, 8);
        $exFee        = round($gross * 0.001, 8);
        $grossPnl     = $gross - $costBasis;
        $pnlAfterFees = $grossPnl - $exFee;
        $perfFee      = $pnlAfterFees > 0 ? round($pnlAfterFees * 0.22, 8) : 0.0;
        $netPnl       = round($pnlAfterFees - $perfFee, 8);

        BotTradeSettlement::create([
            'user_id'              => $userId,
            'bot_buy_execution_id' => $execution->id,
            'bot_sell_order_id'    => $sell->id,
            'gross_revenue'        => $gross,
            'cost_basis'           => $costBasis,
            'network_fee'          => 0,
            'exchange_fee'         => $exFee,
            'spread_fee'           => 0,
            'performance_fee'      => $perfFee,
            'cancel_fee'           => 0,
            'net_pnl'              => $netPnl,
            'settled_at'           => now()->subDays($sellCfg['days_ago'] ?? 1),
        ]);

        $totals['settled_net_pnl'] += $netPnl;
        if ($netPnl > 0) {
            $totals['profit'] += $netPnl;
        }
    }

    // ──────────────────────────── Helpers ──────────────────────────────────

    private function currentPrice(string $symbol): ?float
    {
        if (array_key_exists($symbol, $this->priceCache)) {
            return $this->priceCache[$symbol];
        }

        $marketId = Market::where('base_currency', $symbol)
            ->where('quote_currency', 'USDT')
            ->value('id');

        if (! $marketId) {
            return $this->priceCache[$symbol] = null;
        }

        $price = ExchangePrice::where('market_id', $marketId)->value('price');
        return $this->priceCache[$symbol] = $price !== null ? (float) $price : null;
    }

    private function wipe(array $userIds): void
    {
        $orderIds = BotOrder::whereIn('user_id', $userIds)->pluck('id')->all();
        $execIds  = BotBuyExecution::whereIn('bot_order_id', $orderIds)->pluck('id')->all();

        BotTradeSettlement::whereIn('user_id', $userIds)->delete();
        if (! empty($execIds)) {
            BotSellOrder::whereIn('bot_buy_execution_id', $execIds)->delete();
        }
        BotBuyExecution::whereIn('bot_order_id', $orderIds)->delete();
        BotOrder::whereIn('user_id', $userIds)->delete();
        BotUserSettings::whereIn('user_id', $userIds)->delete();
        BotWallet::whereIn('user_id', $userIds)->delete();
    }
}
