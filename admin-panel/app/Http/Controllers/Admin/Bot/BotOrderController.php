<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Enums\TransactionSubTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BotOrderController extends Controller
{
    /**
     * Landing page: one row per user who has placed at least one bot order,
     * with a quick aggregate of their activity and a user search filter.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');

        $rows = BotOrder::query()
            ->join('users', 'users.id', '=', 'bot_orders.user_id')
            ->selectRaw('users.id as user_id, users.email, users.mobile')
            ->selectRaw('COUNT(bot_orders.id) as orders_count')
            ->selectRaw('SUM(bot_orders.total_amount_usdt) as total_allocated')
            ->selectRaw('MAX(bot_orders.created_at) as last_order_at')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('users.email', 'like', "%{$search}%")
                        ->orWhere('users.mobile', 'like', "%{$search}%");
                });
            })
            ->groupBy('users.id', 'users.email', 'users.mobile')
            ->orderByDesc('last_order_at')
            ->paginate(20)
            ->withQueryString();

        $userIds  = collect($rows->items())->pluck('user_id');
        $wallets  = BotWallet::whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $settings = BotUserSettings::whereIn('user_id', $userIds)->get()->keyBy('user_id');

        return view('dashboard.bot.orders.index', compact('rows', 'wallets', 'settings'));
    }

    /**
     * Per-user overview: a full capital/PnL summary aggregated across ALL of the
     * user's bot orders, plus an auto-trade toggle and the user's order list.
     */
    public function userShow(User $user): View
    {
        $wallet   = BotWallet::firstOrNew(['user_id' => $user->id]);
        $settings = BotUserSettings::firstOrNew(['user_id' => $user->id]);

        // ── Current real-money state (authoritative, from the bot wallet) ──────
        // `balance` already represents the user's total capital in the bot — it is
        // only ever adjusted by realized net_pnl (see SettlementService::updateWallet),
        // while `locked_balance` is just the portion of `balance` currently tied up
        // in open positions. So `balance` alone (NOT balance + locked) is the actual
        // invested capital; adding locked on top would double-count it.
        $balance   = (float) $wallet->balance;
        $locked    = (float) $wallet->locked_balance;
        $withdrawable = max(0, $balance - $locked);
        $actualInvestment = $balance;
        $realizedProfit   = (float) $wallet->profit_balance;

        // ── Lifetime realized figures (from settlements) ──────────────────────
        $pnl = BotTradeSettlement::where('user_id', $user->id)
            ->selectRaw('COALESCE(SUM(net_pnl),0) as net_pnl')
            ->selectRaw('COALESCE(SUM(cost_basis),0) as freed')
            ->selectRaw('COALESCE(SUM(CASE WHEN net_pnl > 0 THEN net_pnl ELSE 0 END),0) as positive_pnl')
            ->selectRaw('COALESCE(SUM(CASE WHEN net_pnl < 0 THEN net_pnl ELSE 0 END),0) as negative_pnl')
            ->selectRaw('COALESCE(SUM(network_fee),0) as network_fee')
            ->selectRaw('COALESCE(SUM(exchange_fee),0) as ref_exchange_fee')
            ->selectRaw('COALESCE(SUM(spread_fee),0) as spread_fee')
            ->selectRaw('COALESCE(SUM(performance_fee),0) as performance_fee')
            ->selectRaw('COALESCE(SUM(cancel_fee),0) as cancel_fee')
            ->first();

        $totalPnl    = (float) $pnl->net_pnl;
        $freedUsdt   = (float) $pnl->freed;
        $positivePnl = (float) $pnl->positive_pnl;
        $negativePnl = abs((float) $pnl->negative_pnl);

        // exchange_fee already combines the buy-side + sell-side fee charged by the
        // reference exchange (CoinEx) — see SettlementService::allocatedBuyExchangeFee().
        $networkFee     = (float) $pnl->network_fee;
        $refExchangeFee = (float) $pnl->ref_exchange_fee;
        $spreadFee      = (float) $pnl->spread_fee;
        $performanceFee = (float) $pnl->performance_fee;
        $cancelFee      = (float) $pnl->cancel_fee;

        // Transfer fees are stored as negative amounts, one row per direction,
        // distinguished by their fixed description text (see BotWalletService).
        $depositTransferFee = abs((float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('subtype', TransactionSubTypeEnum::BOT_TRANSFER_FEE)
            ->where('description', 'کارمزد انتقال به ربات')
            ->sum('amount'));
        $withdrawTransferFee = abs((float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('subtype', TransactionSubTypeEnum::BOT_TRANSFER_FEE)
            ->where('description', 'کارمزد برداشت از ربات')
            ->sum('amount'));

        $tradeFees    = $networkFee + $refExchangeFee + $spreadFee + $performanceFee + $cancelFee;
        $transferFees = $depositTransferFee + $withdrawTransferFee;
        $totalFees    = $tradeFees + $transferFees;

        // Exchange platform revenue = what the platform keeps (performance fee +
        // both transfer fees, which never touch the user's bot wallet balance).
        $platformRevenue = $performanceFee + $transferFees;

        // Gross allocated across all non-skipped executions. This intentionally
        // double-counts reinvested principal — it is the "how much was ever
        // deployed" figure the actual-investment card corrects against.
        $grossAllocated = (float) BotBuyExecution::query()
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $user->id)
            ->where('bot_buy_executions.status', '!=', 'SKIPPED')
            ->sum('bot_buy_executions.allocated_usdt');

        // ── Wallet flows (from transactions) ──────────────────────────────────
        // BOT_TRANSFER_OUT is stored as a negative amount on the main wallet
        // (funds leaving the user's main wallet into the bot), so negate it.
        $deposits = abs((float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('subtype', TransactionSubTypeEnum::BOT_TRANSFER_OUT)
            ->sum('amount'));
        $withdrawals = (float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('subtype', TransactionSubTypeEnum::BOT_TRANSFER_IN)
            ->sum('amount');

        // ── Capital allocation chart percentages ───────────────────────────────
        // withdrawable = actualInvestment - locked by definition, so these always
        // sum to exactly 100% (a real utilization split, not an approximation).
        $lockedPct = $actualInvestment > 0 ? min(100, ($locked / $actualInvestment) * 100) : 0;
        $freePct   = $actualInvestment > 0 ? max(0, 100 - $lockedPct) : 0;

        $orders = BotOrder::where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(15);

        return view('dashboard.bot.orders.user', compact(
            'user', 'settings', 'orders',
            'balance', 'locked', 'withdrawable', 'actualInvestment', 'realizedProfit',
            'totalPnl', 'freedUsdt', 'positivePnl', 'negativePnl', 'grossAllocated',
            'deposits', 'withdrawals', 'lockedPct', 'freePct',
            'tradeFees', 'transferFees', 'totalFees',
            'depositTransferFee', 'withdrawTransferFee', 'refExchangeFee',
            'networkFee', 'spreadFee', 'performanceFee', 'cancelFee',
            'platformRevenue'
        ));
    }

    /**
     * Enable/disable the bot (auto-trade) for a user from the admin panel.
     * Only flips the shared DB flag; the api-service reacts to it on its side.
     */
    public function toggleAutoTrade(User $user): JsonResponse
    {
        $settings = BotUserSettings::firstOrCreate(
            ['user_id' => $user->id],
            ['auto_trade_enabled' => false, 'reinvest_enabled' => false],
        );

        $settings->auto_trade_enabled = ! $settings->auto_trade_enabled;
        $settings->save();

        return response()->json([
            'enabled' => $settings->auto_trade_enabled,
            'message' => $settings->auto_trade_enabled
                ? 'ربات برای این کاربر روشن شد.'
                : 'ربات برای این کاربر خاموش شد.',
        ]);
    }

    public function show(BotOrder $botOrder): View
    {
        $botOrder->load([
            'user',
            'buyExecutions.currency',
            'buyExecutions.sellOrders.settlement',
        ]);

        $settlements = \App\Models\Bot\BotTradeSettlement::query()
            ->whereIn('bot_buy_execution_id', $botOrder->buyExecutions->pluck('id'))
            ->with(['sellOrder', 'buyExecution.currency'])
            ->orderBy('settled_at')
            ->get();

        $symbols = $botOrder->buyExecutions->pluck('currency.symbol')->filter()->unique()->values()->all();
        $markets = \App\Models\Market::whereIn('base_currency', $symbols)
            ->where('quote_currency', 'USDT')
            ->get(['id', 'base_currency'])
            ->keyBy('base_currency');

        // ── Capital summary stats ─────────────────────────────────────────
        $totalInvested = (float) $botOrder->total_amount_usdt;

        // Freed: sum of cost_basis released from locked_balance across all settled positions.
        // cost_basis is populated on every settlement type (fills and cancels), so no filter needed.
        $freedUsdt = $settlements->sum(fn ($s) => (float) $s->cost_basis);

        // Locked: proportional allocated_usdt still sitting in OPEN sell orders
        $lockedUsdt = 0.0;
        foreach ($botOrder->buyExecutions as $exec) {
            if ($exec->status !== 'BOUGHT') {
                continue;
            }
            $openSharePct = $exec->sellOrders->where('status', 'OPEN')->sum('share_percent');
            $lockedUsdt += (float) $exec->allocated_usdt * ((float) $openSharePct / 100);
        }

        $totalPnl    = $settlements->sum(fn ($s) => (float) $s->net_pnl);
        $positivePnl = $settlements->filter(fn ($s) => (float) $s->net_pnl > 0)->sum(fn ($s) => (float) $s->net_pnl);
        $negativePnl = abs($settlements->filter(fn ($s) => (float) $s->net_pnl < 0)->sum(fn ($s) => (float) $s->net_pnl));

        $freedPct  = $totalInvested > 0 ? min(100, ($freedUsdt / $totalInvested) * 100) : 0;
        $lockedPct = $totalInvested > 0 ? min(100 - $freedPct, ($lockedUsdt / $totalInvested) * 100) : 0;

        return view('dashboard.bot.orders.show', compact(
            'botOrder', 'settlements', 'markets',
            'totalInvested', 'freedUsdt', 'lockedUsdt',
            'totalPnl', 'positivePnl', 'negativePnl',
            'freedPct', 'lockedPct'
        ));
    }
}
