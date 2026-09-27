<?php

namespace App\Http\Controllers\Exchange;

use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Exchange;
use App\Services\Exchanges\Asset\SpotOrderServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RefExchangeSpotOrderController extends Controller
{
    public function index(Request $request): View
    {
        $exchanges = $this->supportedExchanges();

        $requestedSlug = $request->input('exchange');
        if ($requestedSlug && $exchanges->contains('slug', $requestedSlug)) {
            Cache::forever($this->selectedExchangeCacheKey($request), $requestedSlug);
        }

        $selectedExchange = $exchanges->firstWhere('slug', Cache::get($this->selectedExchangeCacheKey($request)));

        if (!$selectedExchange) {
            return view('dashboard.exchange.spot_orders.select_exchange', [
                'exchanges' => $exchanges,
            ]);
        }

        $spotOrderService = SpotOrderServiceFactory::make($selectedExchange->slug);

        $currencies = Currency::query()
            ->where('is_active', true)
            ->where('symbol', '!=', 'USDT')
            ->orderBy('symbol')
            ->get();

        $selectedCurrencyId = $request->input('currency_id');
        $selectedCurrency = null;
        $market = null;
        $pendingBuy = ['data' => [], 'pagination' => ['total' => 0, 'has_next' => false]];
        $pendingSell = ['data' => [], 'pagination' => ['total' => 0, 'has_next' => false]];
        $finishedBuy = ['data' => [], 'pagination' => ['total' => 0, 'has_next' => false]];
        $finishedSell = ['data' => [], 'pagination' => ['total' => 0, 'has_next' => false]];
        $balances = null;
        $error = null;

        if ($selectedCurrencyId) {
            $selectedCurrency = $currencies->firstWhere('id', (int) $selectedCurrencyId);

            if ($selectedCurrency) {
                $market = strtoupper($selectedCurrency->symbol) . 'USDT';
                $page = max(1, (int) $request->input('page', 1));
                $limit = min(100, max(1, (int) $request->input('limit', 50)));

                try {
                    $balances = $spotOrderService->getMarketBalances($selectedCurrency->symbol);
                    $pending = $spotOrderService->getPendingOrders($market, null, $page, $limit);
                    $finished = $spotOrderService->getFinishedOrders($market, null, $page, $limit);

                    $pendingBuy = $this->splitBySide($pending, 'buy');
                    $pendingSell = $this->splitBySide($pending, 'sell');
                    $finishedBuy = $this->splitBySide($finished, 'buy');
                    $finishedSell = $this->splitBySide($finished, 'sell');

                    // Preserve exchange totals for summary cards
                    $pendingBuy['pagination']['api_total'] = (int) ($pending['pagination']['total'] ?? 0);
                    $finishedBuy['pagination']['api_total'] = (int) ($finished['pagination']['total'] ?? 0);
                } catch (CantResolveCoinexException $e) {
                    $error = $e->getMessage() ?: 'خطا در برقراری ارتباط با ' . $selectedExchange->name;
                }
            } else {
                $error = 'کوین انتخاب‌شده معتبر نیست.';
            }
        }

        return view('dashboard.exchange.spot_orders.index', [
            'exchanges' => $exchanges,
            'selectedExchange' => $selectedExchange,
            'currencies' => $currencies,
            'selectedCurrency' => $selectedCurrency,
            'market' => $market,
            'balances' => $balances,
            'pendingBuy' => $pendingBuy,
            'pendingSell' => $pendingSell,
            'finishedBuy' => $finishedBuy,
            'finishedSell' => $finishedSell,
            'error' => $error,
            'page' => max(1, (int) $request->input('page', 1)),
            'limit' => min(100, max(1, (int) $request->input('limit', 50))),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exchange' => ['required', Rule::in(SpotOrderServiceFactory::SUPPORTED)],
            'market' => ['required', 'string', 'max:32'],
            'order_id' => ['required', 'integer'],
        ]);

        try {
            $data = SpotOrderServiceFactory::make($validated['exchange'])->cancelOrder(
                $validated['market'],
                $validated['order_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'سفارش با موفقیت لغو شد.',
                'data' => $data,
            ]);
        } catch (CantResolveCoinexException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'خطا در لغو سفارش',
            ], 422);
        }
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exchange' => ['required', Rule::in(SpotOrderServiceFactory::SUPPORTED)],
            'order_id' => ['required', 'regex:/^\d+$/', 'max:32'],
            'currency_id' => ['nullable', 'integer'],
            'market' => ['nullable', 'string', 'max:32'],
        ]);

        $spotOrderService = SpotOrderServiceFactory::make($validated['exchange']);
        $orderId = (string) $validated['order_id'];
        $market = isset($validated['market']) ? strtoupper(trim($validated['market'])) : null;

        if ($market === null && !empty($validated['currency_id'])) {
            $currency = Currency::query()
                ->where('is_active', true)
                ->where('symbol', '!=', 'USDT')
                ->find((int) $validated['currency_id']);

            if (!$currency) {
                return response()->json([
                    'success' => false,
                    'message' => 'کوین انتخاب‌شده معتبر نیست.',
                ], 422);
            }

            $market = strtoupper($currency->symbol).'USDT';
        }

        $markets = [];
        if ($market === null) {
            $markets = Currency::query()
                ->where('is_active', true)
                ->where('symbol', '!=', 'USDT')
                ->orderBy('symbol')
                ->pluck('symbol')
                ->map(fn (string $symbol) => strtoupper($symbol).'USDT')
                ->all();
        }

        try {
            $found = $spotOrderService->findOrderById($orderId, $market, $markets);
        } catch (CantResolveCoinexException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'خطا در دریافت سفارش از صرافی مرجع',
            ], 422);
        }

        $resolvedMarket = $found['market'];
        $order = $found['order'];

        $deals = [];
        $dealsError = null;

        try {
            $deals = $spotOrderService->getOrderDeals($resolvedMarket, $orderId)['data'] ?? [];
        } catch (CantResolveCoinexException $e) {
            $dealsError = $e->getMessage() ?: 'خطا در دریافت معاملات سفارش';
        }

        return response()->json([
            'success' => true,
            'market' => $resolvedMarket,
            'order' => $order,
            'deals' => $deals,
            'deals_error' => $dealsError,
        ]);
    }

    /**
     * @return Collection<int, Exchange>
     */
    private function supportedExchanges(): Collection
    {
        return Exchange::query()
            ->whereIn('slug', SpotOrderServiceFactory::SUPPORTED)
            ->orderBy('priority')
            ->get();
    }

    private function selectedExchangeCacheKey(Request $request): string
    {
        return 'ref-exchange-spot-orders:selected-exchange:' . $request->user()->getAuthIdentifier();
    }

    private function splitBySide(array $result, string $side): array
    {
        $filtered = array_values(array_filter(
            $result['data'] ?? [],
            fn ($order) => strtolower((string) ($order['side'] ?? '')) === $side
        ));

        return [
            'data' => $filtered,
            'pagination' => [
                'total' => count($filtered),
                'has_next' => (bool) ($result['pagination']['has_next'] ?? false),
            ],
        ];
    }
}
