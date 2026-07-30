<?php

namespace App\Http\Controllers\Exchange;

use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\Exchanges\Asset\Coinex\CoinexSpotOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoinexSpotOrderController extends Controller
{
    public function __construct(
        private readonly CoinexSpotOrderService $coinexSpotOrderService
    ) {}

    public function index(Request $request): View
    {
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
                    $balances = $this->coinexSpotOrderService->getMarketBalances($selectedCurrency->symbol);
                    $pending = $this->coinexSpotOrderService->getPendingOrders($market, null, $page, $limit);
                    $finished = $this->coinexSpotOrderService->getFinishedOrders($market, null, $page, $limit);

                    $pendingBuy = $this->splitBySide($pending, 'buy');
                    $pendingSell = $this->splitBySide($pending, 'sell');
                    $finishedBuy = $this->splitBySide($finished, 'buy');
                    $finishedSell = $this->splitBySide($finished, 'sell');

                    // Preserve CoinEx totals for summary cards
                    $pendingBuy['pagination']['api_total'] = (int) ($pending['pagination']['total'] ?? 0);
                    $finishedBuy['pagination']['api_total'] = (int) ($finished['pagination']['total'] ?? 0);
                } catch (CantResolveCoinexException $e) {
                    $error = $e->getMessage() ?: 'خطا در برقراری ارتباط با CoinEx';
                }
            } else {
                $error = 'کوین انتخاب‌شده معتبر نیست.';
            }
        }

        return view('dashboard.exchange.coinex_spot_orders.index', [
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
            'market' => ['required', 'string', 'max:32'],
            'order_id' => ['required', 'integer'],
        ]);

        try {
            $data = $this->coinexSpotOrderService->cancelOrder(
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
