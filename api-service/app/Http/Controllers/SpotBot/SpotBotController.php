<?php

namespace App\Http\Controllers\SpotBot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\SpotOrderSourceEnum;
use App\Exceptions\V1\Wallet\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Helpers\Math;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Setting;
use App\Models\SpotBotSetting;
use App\Models\SpotOrder;
use App\Services\Spot\DTO\SpotOrderRequestDTO;
use App\Services\Spot\OrderMatchingEngine;
use App\Services\Spot\SpotService;
use App\Services\SpotBot\InMemoryOrderBookService;
use App\Services\SpotBot\DTO\InMemoryBotOrderDTO;
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
                    $buyPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'buy', $i, $market);
                    $quantity = $this->calculateOrderQuantity($setting, $market);

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
                    $sellPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'sell', $i, $market);
                    $quantity = $this->calculateOrderQuantity($setting, $market);

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

            // Broadcast orderbook update after generating all orders
            \App\Jobs\BroadcastOrderBook::dispatch($market->id);

            // Trigger matching for existing user orders with newly created bot orders
            $matchingResult = $this->triggerMatchingForExistingOrders($market->id);

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
                    'matched_user_orders' => $matchingResult['matched_orders'],
                    'matching_errors' => $matchingResult['errors'],
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
     * Create a bot order in Redis (in-memory) instead of database
     * Orders will only be persisted to database when they are matched
     *
     * @param int $userId
     * @param int $marketId
     * @param string $quantity
     * @param string $price
     * @param SpotOrderSideEnum $side
     * @param SpotOrderTypeEnum $type
     * @return InMemoryBotOrderDTO
     */
    private function createBotOrder(int $userId, int $marketId, string $quantity, string $price, SpotOrderSideEnum $side, SpotOrderTypeEnum $type): InMemoryBotOrderDTO
    {
        $wallets = resolve(WalletRepositoryInterface::class);
        $inMemoryOrderBook = resolve(InMemoryOrderBookService::class);

        $lock = Cache::lock('bot_trade:' . $marketId . $userId, 40);

        if ($lock->get()) {
            try {
                // Validate balances without locking funds; bot orders do not use locked_balance
                $market = Market::find($marketId);

                if ($side === SpotOrderSideEnum::BUY) {
                    // For buy orders, ensure sufficient quote currency (no lock)
                    $totalValue = Math::mul($quantity, $price);
                    $wallet = $wallets->getOneOrCreateByCurrencyWithLock($market->quote_currency, $userId);

                    if (Math::comp($wallet->available_balance, $totalValue) === -1) {
                        throw new InsufficientBalanceException("Insufficient {$market->quote_currency} balance.");
                    }
                } else {
                    // For sell orders, ensure sufficient base currency (no lock)
                    $wallet = $wallets->getOneOrCreateByCurrencyWithLock($market->base_currency, $userId);

                    if (Math::comp($wallet->available_balance, $quantity) === -1) {
                        throw new InsufficientBalanceException("Insufficient {$market->base_currency} balance.");
                    }
                }

                // Create in-memory order
                $order = new InMemoryBotOrderDTO([
                    'id' => $inMemoryOrderBook->generateOrderId(),
                    'user_id' => $userId,
                    'market_id' => $marketId,
                    'quantity' => $quantity,
                    'filled_quantity' => '0',
                    'price' => $price,
                    'side' => $side,
                    'type' => $type,
                    'status' => SpotOrderStatusEnum::OPEN,
                    'created_at' => now()->timestamp,
                    'updated_at' => now()->timestamp,
                ]);

                // Store in Redis
                $inMemoryOrderBook->storeOrder($order);

                return $order;
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
        $buyQuantity = $this->calculateOrderQuantity($setting, $market);
        $buyPricePreview = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'buy', 0, $market);
        $totalBuyValue = Math::mul($buyQuantity, $buyPricePreview);
        $quoteWallet = $wallets->getOneOrCreateByCurrencyWithLock($market->quote_currency, $setting->fake_user_id);
        if (Math::comp($quoteWallet->available_balance, $totalBuyValue) === -1) {
            throw new InsufficientBalanceException("Insufficient {$market->quote_currency} balance.");
        }

        // SELL side check: need base currency quantity for a representative order
        $sellQuantity = $this->calculateOrderQuantity($setting, $market);
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
     * @param Market $market
     * @return string
     */
    private function calculateOrderPrice(string $currentPrice, string $margin, string $side, int $orderIndex, Market $market): string
    {
        $marginMultiplier = (1 + ($orderIndex * 0.1)); // Spread orders with increasing margin
        $adjustedMargin = bcmul($margin, (string)$marginMultiplier, 8);

        if ($side === 'buy') {
            // Buy orders below current price
            $priceReduction = bcmul($currentPrice, bcdiv($adjustedMargin, '100', 8), 8);
            $calculatedPrice = bcsub($currentPrice, $priceReduction, 8);
        } else {
            // Sell orders above current price
            $priceIncrease = bcmul($currentPrice, bcdiv($adjustedMargin, '100', 8), 8);
            $calculatedPrice = bcadd($currentPrice, $priceIncrease, 8);
        }

        // Apply price precision truncation
        $pricePrecision = $market->currency?->price_precision;
        if ($pricePrecision !== null) {
            return $this->truncateToPrecision($calculatedPrice, (int) $pricePrecision);
        }

        return $calculatedPrice;
    }

    /**
     * Calculate order quantity based on bot settings
     *
     * @param SpotBotSetting $setting
     * @param Market $market
     * @return string
     */
    private function calculateOrderQuantity(SpotBotSetting $setting, Market $market): string
    {
        // Generate random quantity between min and max order size
        $minSize = $setting->min_order_size ?? '0.001';
        $maxSize = $setting->max_order_size ?? '1.0';

        // Simple random quantity generation (you can make this more sophisticated)
        $randomFactor = mt_rand(0, 100) / 100; // 0 to 1
        $sizeDiff = bcsub($maxSize, $minSize, 8);
        $randomAmount = bcmul($sizeDiff, (string)$randomFactor, 8);

        $calculatedQuantity = bcadd($minSize, $randomAmount, 8);

        // Apply amount precision validation and truncation
        $amountPrecision = $market->currency?->amount_precision;
        if ($amountPrecision !== null) {
            // Validate precision first
            if (!$this->isPrecisionValid($calculatedQuantity, (int) $amountPrecision)) {
                // If precision is invalid, truncate to valid precision
                $calculatedQuantity = $this->truncateToPrecision($calculatedQuantity, (int) $amountPrecision);
            }
        }

        return $calculatedQuantity;
    }

    /**
     * Cancel bot orders (both Redis and partially filled DB orders) for a specific market
     * 
     * @param int $userId Bot user ID
     * @param int $marketId Market ID
     * @param Market $market Market model
     * @return array ['cancelled_redis' => int, 'cancelled_db' => int, 'errors' => array]
     */
    private function cancelBotOrdersForMarket(int $userId, int $marketId, Market $market): array
    {
        $inMemoryOrderBook = resolve(InMemoryOrderBookService::class);
        $spotService = resolve(SpotService::class);

        $cancelledRedis = 0;
        $cancelledDb = 0;
        $errors = [];

        try {
            $cancelledRedis += $inMemoryOrderBook->deleteUserMarketOrders($userId, $marketId);
        } catch (Throwable $e) {
            $errors[] = 'Redis orders deletion failed: ' . $e->getMessage();
            Log::channel('spot-bot')->error('Failed to delete Redis bot orders for user market', [
                'user_id' => $userId,
                'market_id' => $marketId,
                'error' => $e->getMessage()
            ]);
        }

        // 2. Cancel bot orders in database (both partially filled and unfilled)
        $dbOrders = SpotOrder::where('market_id', $marketId)
            ->where('user_id', $userId)
            ->where('source', SpotOrderSourceEnum::BOT)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->whereColumn('filled_quantity', '>=', \DB::raw('0')) // Include unfilled (0) and partially filled (>0)
            ->get();

        foreach ($dbOrders as $order) {
            try {
                $spotService->cancel($userId, $order->id);
                $cancelledDb++;
            } catch (Throwable $e) {
                $errors[] = "DB order {$order->id}: " . $e->getMessage();
                Log::channel('spot-bot')->error('Failed to cancel DB bot order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 3. Ensure Redis market indexes are fully cleared to avoid stale orderIds
        try {
            $inMemoryOrderBook->clearMarketOrdersIndex($marketId, SpotOrderSideEnum::BUY);
            $inMemoryOrderBook->clearMarketOrdersIndex($marketId, SpotOrderSideEnum::SELL);
            $inMemoryOrderBook->clearUserMarketOrdersIndex($userId, $marketId);
        } catch (Throwable $e) {
            Log::channel('spot-bot')->error('Failed clearing Redis indexes after cancellation', [
                'market_id' => $marketId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'cancelled_redis' => $cancelledRedis,
            'cancelled_db' => $cancelledDb,
            'total_cancelled' => $cancelledRedis + $cancelledDb,
            'errors' => $errors,
        ];
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
            // Cancel both Redis and partially filled DB orders
            $result = $this->cancelBotOrdersForMarket($setting->fake_user_id, $market->id, $market);

            // Broadcast orderbook update after cancelling orders
            \App\Jobs\BroadcastOrderBook::dispatch($market->id);

            return response()->json([
                'success' => true,
                'message' => "Bot orders cancelled successfully for {$currency->name}",
                'data' => [
                    'currency' => $currency->symbol,
                    'market_id' => $market->id,
                    'cancelled_redis_orders' => $result['cancelled_redis'],
                    'cancelled_db_orders' => $result['cancelled_db'],
                    'total_cancelled' => $result['total_cancelled'],
                    'errors' => $result['errors']
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
     * Replace bot orders for specific currency (atomic operation)
     * This method cancels existing orders and immediately creates new ones
     * to prevent orderbook from appearing empty
     *
     * @param Request $request
     * @param int $currency_id
     * @return JsonResponse
     */
    public function replaceOrders(Request $request, int $currency_id): JsonResponse
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

        try {
            // Pre-check balances before starting the replacement process
            $this->ensureSufficientBalances($setting, $market, $currentPrice);

            $buyOrdersCount = $setting->buy_orders_count;
            $sellOrdersCount = $setting->sell_orders_count;

            // Step 1: Generate new orders first (but don't place them yet)
            $newOrders = [];
            $errors = [];

            // Prepare buy orders
            for ($i = 0; $i < $buyOrdersCount; $i++) {
                try {
                    $buyPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'buy', $i, $market);
                    $quantity = $this->calculateOrderQuantity($setting, $market);

                    $newOrders[] = [
                        'side' => SpotOrderSideEnum::BUY,
                        'price' => $buyPrice,
                        'quantity' => $quantity,
                        'type' => SpotOrderTypeEnum::LIMIT
                    ];
                } catch (\Throwable $e) {
                    $errors[] = "Preparing buy order {$i}: " . $e->getMessage();
                }
            }

            // Prepare sell orders
            for ($i = 0; $i < $sellOrdersCount; $i++) {
                try {
                    $sellPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'sell', $i, $market);
                    $quantity = $this->calculateOrderQuantity($setting, $market);

                    $newOrders[] = [
                        'side' => SpotOrderSideEnum::SELL,
                        'price' => $sellPrice,
                        'quantity' => $quantity,
                        'type' => SpotOrderTypeEnum::LIMIT
                    ];
                } catch (\Throwable $e) {
                    $errors[] = "Preparing sell order {$i}: " . $e->getMessage();
                }
            }

            // Step 2: Cancel existing orders (both Redis and partially filled DB orders)
            $cancelResult = $this->cancelBotOrdersForMarket($setting->fake_user_id, $market->id, $market);

            // Merge any cancellation errors with preparation errors
            $errors = array_merge($errors, $cancelResult['errors']);

            $createdOrders = [];

            // Immediately create new orders
            foreach ($newOrders as $orderData) {
                try {
                    $newOrder = $this->createBotOrder(
                        $setting->fake_user_id,
                        $market->id,
                        $orderData['quantity'],
                        $orderData['price'],
                        $orderData['side'],
                        $orderData['type']
                    );

                    if ($newOrder) {
                        $createdOrders[] = [
                            'side' => $orderData['side']->value,
                            'price' => $orderData['price'],
                            'quantity' => $orderData['quantity'],
                            'order_id' => $newOrder->id
                        ];
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Creating new {$orderData['side']->value} order: " . $e->getMessage();
                }
            }

            // Final broadcast after all replacements are done
            \App\Jobs\BroadcastOrderBook::dispatch($market->id);

            // Trigger matching for existing user orders with newly created bot orders
            $matchingResult = $this->triggerMatchingForExistingOrders($market->id);

            return response()->json([
                'success' => true,
                'message' => "Bot orders replaced successfully for {$currency->name}",
                'data' => [
                    'currency' => $currency->symbol,
                    'market_id' => $market->id,
                    'current_price' => $currentPrice,
                    'cancelled_redis_orders' => $cancelResult['cancelled_redis'],
                    'cancelled_db_orders' => $cancelResult['cancelled_db'],
                    'total_cancelled' => $cancelResult['total_cancelled'],
                    'created_orders_count' => count($createdOrders),
                    'created_orders' => $createdOrders,
                    'matched_user_orders' => $matchingResult['matched_orders'],
                    'matching_errors' => $matchingResult['errors'],
                    'errors' => $errors
                ]
            ]);
        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Throwable $exception) {
            Log::channel('spot-bot')->error('Bot order replacement failed', [
                'currency_id' => $currency_id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to replace bot orders: ' . $exception->getMessage()
            ], 500);
        }
    }

    // Bot orders no longer use locked_balance; helper removed

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

                // Cancel both Redis and partially filled DB orders (bot orders don't use locked_balance)
                $cancelResult = $this->cancelBotOrdersForMarket($setting->fake_user_id, $market->id, $market);

                $totalCancelled += $cancelResult['total_cancelled'];
                $totalErrors += count($cancelResult['errors']);

                \App\Jobs\BroadcastOrderBook::dispatch($market->id);

                $results[] = [
                    'currency_id' => $setting->currency_id,
                    'currency_name' => $setting->currency->name,
                    'status' => 'processed',
                    'cancelled_redis_orders' => $cancelResult['cancelled_redis'],
                    'cancelled_db_orders' => $cancelResult['cancelled_db'],
                    'total_cancelled' => $cancelResult['total_cancelled'],
                    'errors_count' => count($cancelResult['errors'])
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
                        $buyPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'buy', $i, $market);
                        $quantity = $this->calculateOrderQuantity($setting, $market);

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
                        $sellPrice = $this->calculateOrderPrice($currentPrice, $setting->order_margin, 'sell', $i, $market);
                        $quantity = $this->calculateOrderQuantity($setting, $market);

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

                // Trigger matching for existing user orders with newly created bot orders
                $matchingResult = $this->triggerMatchingForExistingOrders($market->id);

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
                    'matched_user_orders' => $matchingResult['matched_orders'],
                    'matching_errors' => $matchingResult['errors'],
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

    /**
     * Truncate a number to specified decimal precision without rounding
     *
     * @param string $value
     * @param int $precision
     * @return string
     */
    private function truncateToPrecision(string $value, int $precision): string
    {
        if ($precision < 0) {
            return $value;
        }

        $parts = explode('.', $value);

        if (count($parts) === 1) {
            // No decimal part
            return $value;
        }

        if ($precision === 0) {
            return $parts[0];
        }

        $decimalPart = substr($parts[1], 0, $precision);
        return $parts[0] . '.' . $decimalPart;
    }

    /**
     * Check if a value's decimal precision is valid (not exceeding the allowed precision)
     *
     * @param string $value
     * @param int $precision
     * @return bool
     */
    private function isPrecisionValid(string $value, int $precision): bool
    {
        $parts = explode('.', $value);

        if (count($parts) === 1) {
            return true; // No decimal part, precision is valid
        }

        $decimalPart = $parts[1];
        return strlen($decimalPart) <= $precision;
    }

    /**
     * Trigger matching engine for existing user orders after bot creates new orders
     * This ensures user maker orders get matched with newly created bot orders
     * 
     * @param int $marketId
     * @return array ['matched_orders' => int, 'errors' => array]
     */
    private function triggerMatchingForExistingOrders(int $marketId): array
    {
        $matchedOrders = 0;
        $errors = [];

        try {
            // Get all open user orders (non-bot) for this market
            $userOrders = SpotOrder::query()
                ->where('market_id', $marketId)
                ->where('status', SpotOrderStatusEnum::OPEN)
                ->where('source', '!=', SpotOrderSourceEnum::BOT)
                ->orderBy('created_at', 'asc') // Process older orders first
                ->get();

            if ($userOrders->isEmpty()) {
                Log::channel('spot-bot')->info('No user orders to match for market', [
                    'market_id' => $marketId
                ]);
                return ['matched_orders' => 0, 'errors' => []];
            }

            $orderMatchingEngine = resolve(OrderMatchingEngine::class);

            foreach ($userOrders as $order) {
                try {
                    // Refresh order to get latest state
                    $order->refresh();

                    // Skip if order is no longer open (might have been matched by another process)
                    if ($order->status !== SpotOrderStatusEnum::OPEN) {
                        continue;
                    }

                    // Store initial filled quantity to detect if matching occurred
                    $initialFilledQuantity = $order->filled_quantity;

                    // Try to match this order with bot orders
                    if ($order->type === SpotOrderTypeEnum::LIMIT) {
                        $orderMatchingEngine->limit($order);
                    } elseif ($order->type === SpotOrderTypeEnum::MARKET) {
                        $orderMatchingEngine->market($order);
                    }

                    // Check if order was matched (status changed or filled_quantity increased)
                    $order->refresh();
                    if ($order->status === SpotOrderStatusEnum::COMPLETED || 
                        Math::comp($order->filled_quantity, $initialFilledQuantity) === 1) {
                        $matchedOrders++;
                        Log::channel('spot-bot')->info('User order matched with bot orders', [
                            'order_id' => $order->id,
                            'user_id' => $order->user_id,
                            'market_id' => $marketId,
                            'initial_filled' => $initialFilledQuantity,
                            'final_filled' => $order->filled_quantity,
                            'status' => $order->status->value
                        ]);
                    }
                } catch (Throwable $e) {
                    $errors[] = "Order {$order->id}: " . $e->getMessage();
                    Log::channel('spot-bot')->error('Failed to match user order with bot orders', [
                        'order_id' => $order->id,
                        'market_id' => $marketId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::channel('spot-bot')->info('Completed matching existing user orders with bot orders', [
                'market_id' => $marketId,
                'total_user_orders' => $userOrders->count(),
                'matched_orders' => $matchedOrders,
                'errors_count' => count($errors)
            ]);
        } catch (Throwable $e) {
            Log::channel('spot-bot')->error('Failed to trigger matching for existing orders', [
                'market_id' => $marketId,
                'error' => $e->getMessage()
            ]);
            $errors[] = 'Matching process failed: ' . $e->getMessage();
        }

        return [
            'matched_orders' => $matchedOrders,
            'errors' => $errors
        ];
    }
}
