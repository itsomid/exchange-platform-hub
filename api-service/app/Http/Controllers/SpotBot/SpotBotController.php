<?php

namespace App\Http\Controllers\SpotBot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Exceptions\V1\Wallet\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Helpers\Math;
use App\Models\Currency;
use App\Models\Market;
use App\Models\SpotBotSetting;
use App\Models\SpotOrder;
use App\Services\Spot\DTO\SpotOrderRequestDTO;
use App\Services\Spot\OrderMatchingEngine;
use App\Services\Spot\SpotService;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SpotBotController extends Controller
{
    /**
     * Get bot status for specific currency
     *
     * @param int $currency_id
     * @return JsonResponse
     */
    public function getBotStatus(int $currency_id): JsonResponse
    {
        $currency = Currency::find($currency_id);

        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found'
            ], 404);
        }

        $setting = SpotBotSetting::with(['currency', 'fakeUser'])
            ->where('currency_id', $currency_id)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Bot settings not found for this currency'
            ], 404);
        }

        // Add currency_symbol to the setting
        $setting->currency_symbol = $setting->currency ? $setting->currency->symbol : null;

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }



    /**
     * Sync orders for specific currency
     *
     * @param Request $request
     * @param int $currency_id
     * @return JsonResponse
     */
    public function syncOrders(Request $request, int $currency_id): JsonResponse
    {
        $currency = Currency::find($currency_id);

        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found'
            ], 404);
        }

        $setting = SpotBotSetting::where('currency_id', $currency_id)->first();

        if (!$setting || !$setting->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Bot is not active for this currency'
            ], 400);
        }

        // TODO: Implement order synchronization logic for specific currency
        return response()->json([
            'success' => true,
            'message' => "Orders synchronized successfully for {$currency->name}"
        ]);
    }



    /**
     * Generate orders for specific currency
     *
     * @param Request $request
     * @param int $currency_id
     * @return JsonResponse
     */
    public function generateOrders(Request $request, int $currency_id): JsonResponse
    {
        $currency = Currency::find($currency_id);

        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found'
            ], 404);
        }

        $setting = SpotBotSetting::where('currency_id', $currency_id)->first();

        if (!$setting || !$setting->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Bot is not active for this currency'
            ], 400);
        }

        // Get optional parameters from request, fallback to settings
        $buyOrdersCount = $request->input('buy_orders_count', $setting->buy_orders_count);
        $sellOrdersCount = $request->input('sell_orders_count', $setting->sell_orders_count);

        // Find the market for this currency (assuming USDT as quote currency)
        $market = Market::where('base_currency', $currency->symbol)
            ->where('quote_currency', 'USDT')
            ->where('is_active', true)
            ->first();

        if (!$market) {
            return response()->json([
                'success' => false,
                'message' => 'No active market found for this currency'
            ], 400);
        }

        // Get current market price
        $currentPrice = $market->exchangePrice?->price;
        if (!$currentPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Market price not available'
            ], 400);
        }

        $generatedOrders = [];
        $errors = [];

        // Pre-check both buy and sell balances before creating any orders
        try {
            $this->ensureSufficientBalances($setting, $market, $currentPrice);
        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate balances: ' . $e->getMessage()
            ], 500);
        }

        try {
            // Generate buy orders
            for ($i = 0; $i < $buyOrdersCount; $i++) {
                try {
                    $buyPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'buy', $i);
                    $quantity = $this->calculateOrderQuantity($setting);

                    $buyOrder = $this->createBotOrder(
                        $setting->fake_user_id,
                        $market->id,
                        $quantity,
                        $buyPrice,
                        SpotOrderSideEnum::BUY,
                        SpotOrderTypeEnum::LIMIT
                    );

                    if ($buyOrder) {
                        $generatedOrders[] = [
                            'side' => 'buy',
                            'price' => $buyPrice,
                            'quantity' => $quantity,
                            'order_id' => $buyOrder->id ?? null
                        ];
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Buy order {$i}: " . $e->getMessage();
                }
            }

            // Generate sell orders
            for ($i = 0; $i < $sellOrdersCount; $i++) {
                try {
                    $sellPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'sell', $i);
                    $quantity = $this->calculateOrderQuantity($setting);

                    $sellOrder = $this->createBotOrder(
                        $setting->fake_user_id,
                        $market->id,
                        $quantity,
                        $sellPrice,
                        SpotOrderSideEnum::SELL,
                        SpotOrderTypeEnum::LIMIT
                    );

                    if ($sellOrder) {
                        $generatedOrders[] = [
                            'side' => 'sell',
                            'price' => $sellPrice,
                            'quantity' => $quantity,
                            'order_id' => $sellOrder->id ?? null
                        ];
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Sell order {$i}: " . $e->getMessage();
                }
            }

            Log::channel('spot-bot')->info('Bot orders generated successfully', [
                'currency_id' => $currency_id,
                'currency_symbol' => $currency->symbol,
                'market_id' => $market->id,
                'total_orders_generated' => count($generatedOrders),
                'buy_orders_count' => $buyOrdersCount,
                'sell_orders_count' => $sellOrdersCount,
                'order_margin' => $setting->order_margin
            ]);

            return response()->json([
                'success' => true,
                'message' => "Orders generated successfully for {$currency->name}",
                'data' => [
                    'currency' => $currency->symbol,
                    'market_id' => $market->id,
                    'current_price' => $currentPrice,
                    'total_orders_generated' => count($generatedOrders),
                    'buy_orders_count' => $buyOrdersCount,
                    'sell_orders_count' => $sellOrdersCount,
                    'order_margin' => $setting->order_margin,
                    'orders' => $generatedOrders,
                    'errors' => $errors
                ]
            ]);
        } catch (Throwable $exception) {
            Log::channel('spot-bot')->error('Bot order generation failed', [
                'currency_id' => $currency_id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate orders: ' . $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Create a bot order using the same logic as user orders
     *
     * @param int $userId
     * @param int $marketId
     * @param string $quantity
     * @param string $price
     * @param SpotOrderSideEnum $side
     * @param SpotOrderTypeEnum $type
     * @return mixed
     */
    private function createBotOrder(int $userId, int $marketId, string $quantity, string $price, SpotOrderSideEnum $side, SpotOrderTypeEnum $type)
    {
        $lock = Cache::lock('bot_trade:' . $marketId . $userId, 40);

        if ($lock->get()) {
            try {
                $spotService = resolve(SpotService::class);
                $response = $spotService->trade(
                    resolve(SpotOrderRequestDTO::class)
                        ->setUserId($userId)
                        ->setQuantity($quantity)
                        ->setType($type)
                        ->setSide($side)
                        ->setPrice($price)
                        ->setMarketId($marketId)
                );

                $orderMatchingEngine = resolve(OrderMatchingEngine::class);
                if ($type === SpotOrderTypeEnum::MARKET) {
                    $orderMatchingEngine->market($response->getSpotOrderModel());
                } elseif ($type === SpotOrderTypeEnum::LIMIT) {
                    $orderMatchingEngine->limit($response->getSpotOrderModel());
                }

                return $response->getSpotOrderModel();
            } catch (Throwable $exception) {
                Log::channel('spot-bot')->error('Bot order creation failed', [
                    'user_id' => $userId,
                    'market_id' => $marketId,
                    'side' => $side->value,
                    'error' => $exception->getMessage()
                ]);
                throw $exception;
            } finally {
                $lock->release();
            }
        }

        throw new \Exception('Could not acquire lock for bot order creation');
    }

    /**
     * Ensure both sides (buy and sell) have sufficient balances before creating any orders
     */
    private function ensureSufficientBalances(SpotBotSetting $setting, Market $market, string $currentPrice): void
    {
        $wallets = resolve(WalletRepositoryInterface::class);

        // BUY side check: need quote currency balance for total buy value of a representative order
        $buyQuantity = $this->calculateOrderQuantity($setting);
        $buyPricePreview = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'buy', 0);
        $totalBuyValue = Math::mul($buyQuantity, $buyPricePreview);
        $quoteWallet = $wallets->getOneOrCreateByCurrencyWithLock($market->quote_currency, $setting->fake_user_id);
        if (Math::comp($quoteWallet->available_balance, $totalBuyValue) === -1) {
            throw new InsufficientBalanceException("Insufficient {$market->quote_currency} balance.");
        }

        // SELL side check: need base currency quantity for a representative order
        $sellQuantity = $this->calculateOrderQuantity($setting);
        $baseWallet = $wallets->getOneOrCreateByCurrencyWithLock($market->base_currency, $setting->fake_user_id);
        if (Math::comp($baseWallet->available_balance, $sellQuantity) === -1) {
            throw new InsufficientBalanceException("Insufficient {$market->base_currency} balance.");
        }
    }

    /**
     * Calculate order price based on current price and margin
     *
     * @param string $currentPrice
     * @param string $margin
     * @param string $side
     * @param int $orderIndex
     * @return string
     */
    private function calculateOrderPrice(string $currentPrice, string $margin, string $side, int $orderIndex): string
    {
        $marginMultiplier = (1 + ($orderIndex * 0.1)); // Spread orders with increasing margin
        $adjustedMargin = bcmul($margin, (string)$marginMultiplier, 8);

        if ($side === 'buy') {
            // Buy orders below current price
            $priceReduction = bcmul($currentPrice, bcdiv($adjustedMargin, '100', 8), 8);
            return bcsub($currentPrice, $priceReduction, 8);
        } else {
            // Sell orders above current price
            $priceIncrease = bcmul($currentPrice, bcdiv($adjustedMargin, '100', 8), 8);
            return bcadd($currentPrice, $priceIncrease, 8);
        }
    }

    /**
     * Calculate order quantity based on bot settings
     *
     * @param SpotBotSetting $setting
     * @return string
     */
    private function calculateOrderQuantity(SpotBotSetting $setting): string
    {
        // Generate random quantity between min and max order size
        $minSize = $setting->min_order_size ?? '0.001';
        $maxSize = $setting->max_order_size ?? '1.0';

        // Simple random quantity generation (you can make this more sophisticated)
        $randomFactor = mt_rand(0, 100) / 100; // 0 to 1
        $sizeDiff = bcsub($maxSize, $minSize, 8);
        $randomAmount = bcmul($sizeDiff, (string)$randomFactor, 8);

        return bcadd($minSize, $randomAmount, 8);
    }

    /**
     * Match order for specific currency
     *
     * @param Request $request
     * @param int $currency_id
     * @return JsonResponse
     */
    public function matchOrder(Request $request, int $currency_id): JsonResponse
    {
        $currency = Currency::find($currency_id);

        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found'
            ], 404);
        }

        $setting = SpotBotSetting::where('currency_id', $currency_id)->first();

        if (!$setting || !$setting->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Bot is not active for this currency'
            ], 400);
        }

        // TODO: Implement order matching logic for specific currency
        return response()->json([
            'success' => true,
            'message' => "Order matched successfully for {$currency->name}"
        ]);
    }

    /**
     * Sync orders for all active currencies
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncAllOrders(Request $request): JsonResponse
    {
        $activeSettings = SpotBotSetting::with('currency')
            ->where('is_active', true)
            ->get();

        if ($activeSettings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active bot settings found'
            ], 400);
        }

        $results = [];
        foreach ($activeSettings as $setting) {
            // TODO: Implement order synchronization logic for each currency
            $results[] = [
                'currency_id' => $setting->currency_id,
                'currency_name' => $setting->currency->name,
                'status' => 'synchronized'
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Orders synchronized for all active currencies',
            'data' => $results
        ]);
    }

    /**
     * Cancel bot orders for specific currency
     *
     * @param Request $request
     * @param int $currency_id
     * @return JsonResponse
     */
    public function cancelOrders(Request $request, int $currency_id): JsonResponse
    {
        $currency = Currency::find($currency_id);

        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found'
            ], 404);
        }

        $setting = SpotBotSetting::where('currency_id', $currency_id)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Bot setting not found for this currency'
            ], 400);
        }

        // Find the market for this currency (assuming USDT as quote currency)
        $market = Market::where('base_currency', $currency->symbol)
            ->where('quote_currency', 'USDT')
            ->where('is_active', true)
            ->first();

        if (!$market) {
            return response()->json([
                'success' => false,
                'message' => 'No active market found for this currency'
            ], 400);
        }

        try {
            // Get all open bot orders for this currency/market and fake user
            $openOrders = SpotOrder::where('market_id', $market->id)
                ->where('user_id', $setting->fake_user_id)
                ->where('status', SpotOrderStatusEnum::OPEN)
                ->get();

            if ($openOrders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => "No open bot orders found for {$currency->name}",
                    'data' => [
                        'currency' => $currency->symbol,
                        'market_id' => $market->id,
                        'cancelled_orders_count' => 0,
                        'orders' => []
                    ]
                ]);
            }

            $spotService = resolve(SpotService::class);
            $cancelledOrders = [];
            $errors = [];

            foreach ($openOrders as $order) {
                try {
                    $spotService->cancel($setting->fake_user_id, $order->id);
                    $cancelledOrders[] = [
                        'order_id' => $order->id,
                        'side' => $order->side->value,
                        'price' => $order->price,
                        'quantity' => $order->quantity,
                        'filled_quantity' => $order->filled_quantity,
                        'status' => 'cancelled'
                    ];
                } catch (Throwable $exception) {
                    Log::channel('spot-bot')->error('Bot order cancellation failed', [
                        'order_id' => $order->id,
                        'user_id' => $setting->fake_user_id,
                        'market_id' => $market->id,
                        'error' => $exception->getMessage()
                    ]);
                    $errors[] = [
                        'order_id' => $order->id,
                        'error' => $exception->getMessage()
                    ];
                }
            }

            Log::channel('spot-bot')->info('Bot orders cancelled successfully', [
                'currency_id' => $currency_id,
                'currency_symbol' => $currency->symbol,
                'market_id' => $market->id,
                'total_orders_found' => $openOrders->count(),
                'cancelled_orders_count' => count($cancelledOrders),
                'failed_orders_count' => count($errors)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bot orders cancelled successfully for {$currency->name}",
                'data' => [
                    'currency' => $currency->symbol,
                    'market_id' => $market->id,
                    'total_orders_found' => $openOrders->count(),
                    'cancelled_orders_count' => count($cancelledOrders),
                    'failed_orders_count' => count($errors),
                    'orders' => $cancelledOrders,
                    'errors' => $errors
                ]
            ]);
        } catch (Throwable $exception) {
            Log::channel('spot-bot')->error('Bot order cancellation process failed', [
                'currency_id' => $currency_id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel bot orders: ' . $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel bot orders for all active currencies
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelAllOrders(Request $request): JsonResponse
    {
        $activeSettings = SpotBotSetting::with('currency')
            ->where('is_active', true)
            ->get();

        if ($activeSettings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active bot settings found'
            ], 400);
        }

        $results = [];
        $totalCancelled = 0;
        $totalErrors = 0;

        foreach ($activeSettings as $setting) {
            try {
                // Find the market for this currency (assuming USDT as quote currency)
                $market = Market::where('base_currency', $setting->currency->symbol)
                    ->where('quote_currency', 'USDT')
                    ->where('is_active', true)
                    ->first();

                if (!$market) {
                    $results[] = [
                        'currency_id' => $setting->currency_id,
                        'currency_name' => $setting->currency->name,
                        'status' => 'error',
                        'message' => 'No active market found',
                        'cancelled_orders_count' => 0
                    ];
                    continue;
                }

                // Get all open bot orders for this currency/market and fake user
                $openOrders = SpotOrder::where('market_id', $market->id)
                    ->where('user_id', $setting->fake_user_id)
                    ->where('status', SpotOrderStatusEnum::OPEN)
                    ->get();

                $spotService = resolve(SpotService::class);
                $cancelledCount = 0;
                $errorCount = 0;

                foreach ($openOrders as $order) {
                    try {
                        $spotService->cancel($setting->fake_user_id, $order->id);
                        $cancelledCount++;
                    } catch (Throwable $exception) {
                        Log::channel('spot-bot')->error('Bot order cancellation failed', [
                            'order_id' => $order->id,
                            'user_id' => $setting->fake_user_id,
                            'market_id' => $market->id,
                            'currency_id' => $setting->currency_id,
                            'error' => $exception->getMessage()
                        ]);
                        $errorCount++;
                    }
                }

                $totalCancelled += $cancelledCount;
                $totalErrors += $errorCount;

                $results[] = [
                    'currency_id' => $setting->currency_id,
                    'currency_name' => $setting->currency->name,
                    'status' => 'processed',
                    'total_orders_found' => $openOrders->count(),
                    'cancelled_orders_count' => $cancelledCount,
                    'failed_orders_count' => $errorCount
                ];
            } catch (Throwable $exception) {
                Log::channel('spot-bot')->error('Bot order cancellation process failed for currency', [
                    'currency_id' => $setting->currency_id,
                    'error' => $exception->getMessage()
                ]);

                $results[] = [
                    'currency_id' => $setting->currency_id,
                    'currency_name' => $setting->currency->name,
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'cancelled_orders_count' => 0
                ];
                $totalErrors++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bot orders cancellation process completed for all active currencies',
            'data' => [
                'total_currencies_processed' => count($activeSettings),
                'total_orders_cancelled' => $totalCancelled,
                'total_errors' => $totalErrors,
                'results' => $results
            ]
        ]);
    }

    /**
     * Generate orders for all active currencies
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateAllOrders(Request $request): JsonResponse
    {
        $activeSettings = SpotBotSetting::with('currency')
            ->where('is_active', true)
            ->get();

        if ($activeSettings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active bot settings found'
            ], 400);
        }

        $results = [];
        $totalOrdersGenerated = 0;
        $totalErrors = 0;

        foreach ($activeSettings as $setting) {
            try {
                // Find the market for this currency (assuming USDT as quote currency)
                $market = Market::where('base_currency', $setting->currency->symbol)
                    ->where('quote_currency', 'USDT')
                    ->where('is_active', true)
                    ->first();

                if (!$market) {
                    $results[] = [
                        'currency_id' => $setting->currency_id,
                        'currency_name' => $setting->currency->name,
                        'status' => 'error',
                        'message' => 'No active market found',
                        'orders_generated' => 0
                    ];
                    $totalErrors++;
                    continue;
                }

                // Get current market price
                $currentPrice = $market->exchangePrice?->price;
                if (!$currentPrice) {
                    $results[] = [
                        'currency_id' => $setting->currency_id,
                        'currency_name' => $setting->currency->name,
                        'status' => 'error',
                        'message' => 'Market price not available',
                        'orders_generated' => 0
                    ];
                    $totalErrors++;
                    continue;
                }

                $generatedOrders = [];
                $orderErrors = [];

                // Pre-check both sides' balances; if either side insufficient, skip all orders for this currency
                try {
                    $this->ensureSufficientBalances($setting, $market, $currentPrice);
                } catch (InsufficientBalanceException $exception) {
                    Log::channel('spot-bot')->warning('Skipping orders due to insufficient balance', [
                        'currency_id' => $setting->currency_id,
                        'market_id' => $market->id,
                        'error' => $exception->getMessage()
                    ]);
                    $orderErrors[] = $exception->getMessage();
                    // Skip to next setting (no orders)
                    continue;
                }

                // Pre-check both sides already done; proceed to normal generation with no side flags
                for ($i = 0; $i < $setting->buy_orders_count; $i++) {
                    try {
                        $buyPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'buy', $i);
                        $quantity = $this->calculateOrderQuantity($setting);

                        $buyOrder = $this->createBotOrder(
                            $setting->fake_user_id,
                            $market->id,
                            $quantity,
                            $buyPrice,
                            SpotOrderSideEnum::BUY,
                            SpotOrderTypeEnum::LIMIT
                        );

                        if ($buyOrder) {
                            $generatedOrders[] = [
                                'side' => 'buy',
                                'price' => $buyPrice,
                                'quantity' => $quantity,
                                'order_id' => $buyOrder->id ?? null
                            ];
                        }
                    } catch (Throwable $exception) {
                        Log::channel('spot-bot')->error('Bot buy order generation failed', [
                            'currency_id' => $setting->currency_id,
                            'market_id' => $market->id,
                            'order_index' => $i,
                            'error' => $exception->getMessage()
                        ]);
                        $orderErrors[] = "Buy order {$i}: " . $exception->getMessage();
                    }
                }

                for ($i = 0; $i < $setting->sell_orders_count; $i++) {
                    try {
                        $sellPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'sell', $i);
                        $quantity = $this->calculateOrderQuantity($setting);

                        $sellOrder = $this->createBotOrder(
                            $setting->fake_user_id,
                            $market->id,
                            $quantity,
                            $sellPrice,
                            SpotOrderSideEnum::SELL,
                            SpotOrderTypeEnum::LIMIT
                        );

                        if ($sellOrder) {
                            $generatedOrders[] = [
                                'side' => 'sell',
                                'price' => $sellPrice,
                                'quantity' => $quantity,
                                'order_id' => $sellOrder->id ?? null
                            ];
                        }
                    } catch (Throwable $exception) {
                        Log::channel('spot-bot')->error('Bot sell order generation failed', [
                            'currency_id' => $setting->currency_id,
                            'market_id' => $market->id,
                            'order_index' => $i,
                            'error' => $exception->getMessage()
                        ]);
                        $orderErrors[] = "Sell order {$i}: " . $exception->getMessage();
                    }
                }

                $ordersGenerated = count($generatedOrders);
                $totalOrdersGenerated += $ordersGenerated;

                if (!empty($orderErrors)) {
                    $totalErrors += count($orderErrors);
                }

                $results[] = [
                    'currency_id' => $setting->currency_id,
                    'currency_name' => $setting->currency->name,
                    'currency_symbol' => $setting->currency->symbol,
                    'market_id' => $market->id,
                    'current_price' => $currentPrice,
                    'status' => 'processed',
                    'buy_orders_requested' => $setting->buy_orders_count,
                    'sell_orders_requested' => $setting->sell_orders_count,
                    'total_orders_generated' => $ordersGenerated,
                    'order_margin' => $setting->order_margin,
                    'orders' => $generatedOrders,
                    'errors' => $orderErrors
                ];
            } catch (Throwable $exception) {
                Log::channel('spot-bot')->error('Bot order generation process failed for currency', [
                    'currency_id' => $setting->currency_id,
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getMessage()
                ]);

                $results[] = [
                    'currency_id' => $setting->currency_id,
                    'currency_name' => $setting->currency->name,
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'orders_generated' => 0
                ];
                $totalErrors++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order generation process completed for all active currencies',
            'data' => [
                'total_currencies_processed' => count($activeSettings),
                'total_orders_generated' => $totalOrdersGenerated,
                'total_errors' => $totalErrors,
                'results' => $results
            ]
        ]);
    }
}
