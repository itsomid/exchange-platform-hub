<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\Bot\BotWalletTransfer;
use App\Models\User;
use App\Services\Bot\BotAdminApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BotOrderController extends Controller
{
    public function __construct(private readonly BotAdminApiClient $botApi)
    {
    }

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

        // ── Locked-balance breakdown (for the card popover) ────────────────
        // At buy time the FULL allocated_usdt is locked, but settlements only
        // release cost_basis (amount × avg_buy_price). When the reference
        // exchange charged the buy fee in the BASE coin, filled_amount is
        // recorded net of that fee, so cost_basis = allocated − fee and the
        // fee-equivalent stays behind in locked_balance. Split the current
        // locked into "backing open sell tiers" vs that residual.
        $lockedOpenCost = (float) BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $user->id)
            ->where('bot_sell_orders.status', 'OPEN')
            ->selectRaw('COALESCE(SUM(bot_sell_orders.amount_to_sell * bot_buy_executions.avg_buy_price),0) as v')
            ->value('v');
        $lockedResidual = max(0, $locked - $lockedOpenCost);

        // Buy fees the reference exchange charged in the base coin (stored in
        // USDT terms) — the source of the residual above.
        $baseFeeBuys = BotBuyExecution::query()
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $user->id)
            ->where('bot_buy_executions.buy_ref_exchange_fee', '>', 0)
            ->whereNotNull('bot_buy_executions.buy_ref_exchange_fee_currency')
            ->whereRaw("UPPER(bot_buy_executions.buy_ref_exchange_fee_currency) <> 'USDT'")
            ->selectRaw('UPPER(bot_buy_executions.buy_ref_exchange_fee_currency) as fee_currency')
            ->selectRaw('SUM(bot_buy_executions.buy_ref_exchange_fee) as fee_usdt')
            ->groupByRaw('UPPER(bot_buy_executions.buy_ref_exchange_fee_currency)')
            ->get();

        // ── Lifetime realized figures (from settlements) ──────────────────────
        $pnl = BotTradeSettlement::where('user_id', $user->id)
            ->selectRaw('COALESCE(SUM(net_pnl),0) as net_pnl')
            ->selectRaw('COALESCE(SUM(cost_basis),0) as freed')
            ->selectRaw('COALESCE(SUM(gross_revenue),0) as gross_revenue')
            ->selectRaw('COALESCE(SUM(exchange_fee),0) as settled_exchange_fee')
            ->selectRaw('COALESCE(SUM(CASE WHEN net_pnl > 0 THEN net_pnl ELSE 0 END),0) as positive_pnl')
            ->selectRaw('COALESCE(SUM(CASE WHEN net_pnl < 0 THEN net_pnl ELSE 0 END),0) as negative_pnl')
            ->selectRaw('COALESCE(SUM(network_fee),0) as network_fee')
            ->selectRaw('COALESCE(SUM(spread_fee),0) as spread_fee')
            ->selectRaw('COALESCE(SUM(performance_fee),0) as performance_fee')
            ->selectRaw('COALESCE(SUM(cancel_fee),0) as cancel_fee')
            ->first();

        $totalPnl    = (float) $pnl->net_pnl;
        $freedUsdt   = (float) $pnl->freed;
        $positivePnl = (float) $pnl->positive_pnl;
        $negativePnl = abs((float) $pnl->negative_pnl);

        // P&L breakdown for the card popover: net_pnl is exactly
        // price-pnl − (network + exchange + spread + performance + cancel),
        // all summed from the same settlement rows.
        $grossRevenue       = (float) $pnl->gross_revenue;
        $settledExchangeFee = (float) $pnl->settled_exchange_fee;
        $pricePnl           = $grossRevenue - $freedUsdt;

        $networkFee     = (float) $pnl->network_fee;
        $spreadFee      = (float) $pnl->spread_fee;
        $performanceFee = (float) $pnl->performance_fee;
        $cancelFee      = (float) $pnl->cancel_fee;

        // Ref-exchange fee is charged the moment a buy/sell fills on the reference
        // exchange (CoinEx) — it must NOT be read from bot_trade_settlements, since a
        // settlement row for a buy only exists once its sell side later fills/cancels.
        // An open (unsold) position would otherwise show 0 fee despite having already
        // paid a buy-side fee. Sum directly from the source columns instead.
        $buyRefExchangeFee = (float) BotBuyExecution::query()
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $user->id)
            ->where('bot_buy_executions.status', '!=', 'SKIPPED')
            ->sum('bot_buy_executions.buy_ref_exchange_fee');

        $sellRefExchangeFee = (float) BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $user->id)
            ->sum('bot_sell_orders.sell_ref_exchange_fee');

        $refExchangeFee = $buyRefExchangeFee + $sellRefExchangeFee;

        // Transfer fees are read from the dedicated bot_wallet_transfers ledger
        // (one row per transferIn/transferOut call), keyed by direction.
        $depositTransferFee = (float) BotWalletTransfer::where('user_id', $user->id)
            ->where('direction', BotWalletTransfer::DIRECTION_IN)
            ->sum('fee');
        $withdrawTransferFee = (float) BotWalletTransfer::where('user_id', $user->id)
            ->where('direction', BotWalletTransfer::DIRECTION_OUT)
            ->sum('fee');

        $tradeFees    = $networkFee + $refExchangeFee + $spreadFee + $performanceFee + $cancelFee;
        $transferFees = $depositTransferFee + $withdrawTransferFee;
        $totalFees    = $tradeFees + $transferFees;

        // Exchange revenue card is explicitly "deposit fee + performance fee".
        $platformRevenue = $performanceFee + $depositTransferFee;

        // Gross allocated across all non-skipped executions. This intentionally
        // double-counts reinvested principal — it is the "how much was ever
        // deployed" figure the actual-investment card corrects against.
        $grossAllocated = (float) BotBuyExecution::query()
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $user->id)
            ->where('bot_buy_executions.status', '!=', 'SKIPPED')
            ->sum('bot_buy_executions.allocated_usdt');

        // ── Wallet flows (from the bot_wallet_transfers ledger) ─────────────────
        $deposits = (float) BotWalletTransfer::where('user_id', $user->id)
            ->where('direction', BotWalletTransfer::DIRECTION_IN)
            ->sum('gross_amount');
        $withdrawals = (float) BotWalletTransfer::where('user_id', $user->id)
            ->where('direction', BotWalletTransfer::DIRECTION_OUT)
            ->sum('gross_amount');

        // ── Capital allocation chart percentages ───────────────────────────────
        // withdrawable = actualInvestment - locked by definition, so these always
        // sum to exactly 100% (a real utilization split, not an approximation).
        $lockedPct = $actualInvestment > 0 ? min(100, ($locked / $actualInvestment) * 100) : 0;
        $freePct   = $actualInvestment > 0 ? max(0, 100 - $lockedPct) : 0;

        // ── Referral: who introduced this user + how much they've earned from them ──
        $user->loadMissing('introducerReferral.user');
        $introducer = $user->introducerReferral?->user;
        $referralPaid = (float) BotTradeSettlement::where('user_id', $user->id)->sum('referral_fee');

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
            'platformRevenue', 'introducer', 'referralPaid',
            'grossRevenue', 'settledExchangeFee', 'pricePnl',
            'lockedOpenCost', 'lockedResidual', 'baseFeeBuys'
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
            'user.introducerReferral.user',
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

        // ── Referral paid to this user's introducer for this order ──────────────
        $introducer   = $botOrder->user?->introducerReferral?->user;
        $referralPaid = $settlements->sum(fn ($s) => (float) $s->referral_fee);

        return view('dashboard.bot.orders.show', compact(
            'botOrder', 'settlements', 'markets',
            'totalInvested', 'freedUsdt', 'lockedUsdt',
            'totalPnl', 'positivePnl', 'negativePnl',
            'freedPct', 'lockedPct', 'introducer', 'referralPaid'
        ));
    }

    public function updateDescription(Request $request, BotOrder $botOrder): JsonResponse
    {
        $data = $request->validate([
            'admin_description' => 'nullable|string|max:10000',
        ]);

        $botOrder->update([
            'admin_description' => isset($data['admin_description']) && trim($data['admin_description']) !== ''
                ? trim($data['admin_description'])
                : null,
        ]);

        return response()->json([
            'ok'                => true,
            'admin_description' => $botOrder->admin_description,
            'message'           => 'یادداشت ادمین ذخیره شد.',
        ]);
    }

    /* ── Cancel operations (proxied to api-service, where the reference-exchange
       adapter lives). All real work — canceling limit sells on the reference
       exchange and market-selling the freed coin — happens there. ── */

    public function cancelPreview(BotOrder $botOrder): JsonResponse
    {
        return $this->forwardBotApi(fn () => $this->botApi->cancelOrderPreview($botOrder->id));
    }

    public function cancel(BotOrder $botOrder): JsonResponse
    {
        return $this->forwardBotApi(fn () => $this->botApi->cancelOrder($botOrder->id));
    }

    public function cancelAllPreview(User $user): JsonResponse
    {
        return $this->forwardBotApi(fn () => $this->botApi->cancelAllPreview($user->id));
    }

    public function cancelAll(User $user): JsonResponse
    {
        return $this->forwardBotApi(fn () => $this->botApi->cancelAll($user->id));
    }

    private function forwardBotApi(\Closure $call): JsonResponse
    {
        $context = [
            'api_url' => (string) config('smart-bot.api_url'),
            'path'    => request()->path(),
            'method'  => request()->method(),
            'admin_id'=> auth()->id(),
        ];

        try {
            $response = $call();
        } catch (\Throwable $e) {
            Log::error('bot.cancel.forward_exception', $context + [
                'exception' => $e::class,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile().':'.$e->getLine(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'ارتباط با سرویس API برقرار نشد: ' . $e->getMessage(),
            ], 502);
        }

        $status  = $response->status();
        $rawBody = (string) $response->body();
        $payload = $response->json();

        if ($payload === null || ($payload['ok'] ?? true) === false || $status >= 400) {
            Log::warning('bot.cancel.forward_bad_response', $context + [
                'http_status' => $status,
                'ok_flag'     => $payload['ok'] ?? null,
                'error'       => $payload['error'] ?? null,
                'body_snip'   => mb_substr($rawBody, 0, 2000),
            ]);
        } else {
            Log::info('bot.cancel.forward_ok', $context + [
                'http_status' => $status,
                'mode'        => $payload['mode'] ?? null,
                'orders'      => isset($payload['orders']) ? count($payload['orders']) : null,
            ]);
        }

        if ($payload === null) {
            return response()->json([
                'ok'    => false,
                'error' => 'پاسخ نامعتبر از سرویس API.',
                'body'  => $rawBody,
            ], $status ?: 502);
        }

        return response()->json($payload, $status);
    }
}
