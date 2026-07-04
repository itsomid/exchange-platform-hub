<?php

namespace App\Services\Spot;

use App\Enums\RefExchangeSellStatusEnum;
use App\Enums\SpotOrderRoleEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\UserNotification;
use App\Jobs\BroadcastOrderBook;
use App\Jobs\SellOnRefExchangeForSpotTrade;
use App\Helpers\Math;
use App\Models\LockedBalanceDetail;
use App\Enums\SpotOrderSourceEnum;
use App\Models\Market;
use App\Models\Setting;
use App\Models\SpotOrder;
use App\Models\SpotTrade;
use App\Models\TradingCommission;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\SpotBot\InMemoryOrderBookService;
use App\Services\SpotBot\InMemoryOrderPersistenceService;
use App\Services\SpotBot\DTO\InMemoryBotOrderDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class OrderMatchingEngine
{
    public function __construct(
        private WalletRepositoryInterface               $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly InMemoryOrderBookService       $inMemoryOrderBook,
        private readonly InMemoryOrderPersistenceService $persistenceService,
    ) {}

    public function processOrder(): void
    {
        $orders = SpotOrder::query()
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->get();

        foreach ($orders as $order) {
            // Maybe this order matched with another order
            $order->refresh();
            DB::beginTransaction();
            try {
                if ($order->type === SpotOrderTypeEnum::MARKET) {
                    $this->market($order);
                } elseif ($order->type === SpotOrderTypeEnum::LIMIT) {
                    $this->limit($order);
                }
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                \Illuminate\Support\Facades\Log::channel('spot-order-matching')->error('Order matching failed: ' . $e->getMessage(), ['exception' => $e]);
            }
        }
    }

    public function market(SpotOrder $order): void
    {
        $oppositeType = $order->side === SpotOrderSideEnum::BUY ? SpotOrderSideEnum::SELL : SpotOrderSideEnum::BUY;
        $sortType = $order->side === SpotOrderSideEnum::BUY ? 'ASC' : 'DESC';

        // Store initial amount needed to detect partial fills later
        $initialRemindedQuantity = $order->getRemindedQuantity();
        if (Math::comp($initialRemindedQuantity, 0) <= 0) {
            return; // Already filled somehow before processing
        }

        // Get opposite orders from both database and Redis (bot orders)
        $oppositeOrdersData = $this->getOppositeOrders($order, $oppositeType, $sortType);

        foreach ($oppositeOrdersData as $oppositeOrderData) {
            // Double check reminded quantity within the loop
            if ($order->getRemindedQuantity() <= 0) { // Fresh value via model updates
                break;
            }

            // Convert to SpotOrder model (if bot order, will be persisted to DB)
            $oppositeOrder = $this->ensureSpotOrderModel($oppositeOrderData);

            // Price is determined by the matched limit order (maker)
            // No need to set $order->price here, completeOrder uses $makerOrder->price

            // Safety guard: for market BUY orders, verify the buyer has sufficient available
            // balance at the maker's actual price before executing the trade. The pre-order
            // validation uses the best ask, but fills can span multiple price levels, so
            // each fill must be checked individually to prevent negative balances.
            if ($order->side === SpotOrderSideEnum::BUY && $oppositeOrder->price !== null) {
                $proposedTradeQty = Math::comp($order->getRemindedQuantity(), $oppositeOrder->getRemindedQuantity()) <= 0
                    ? $order->getRemindedQuantity()
                    : $oppositeOrder->getRemindedQuantity();
                $requiredCost = Math::mul($proposedTradeQty, $oppositeOrder->price);
                $buyerWallet = $this->walletRepository->getOneOrCreateByCurrencyWithLock(
                    $order->market->quote_currency,
                    $order->user_id
                );
                if (Math::comp($buyerWallet->available_balance, $requiredCost) === -1) {
                    Log::channel('spot-order-matching')->warning(
                        "Market BUY order {$order->id}: insufficient balance "
                        . "({$buyerWallet->available_balance} {$order->market->quote_currency}) "
                        . "for required trade cost {$requiredCost} at price {$oppositeOrder->price}. "
                        . 'Cancelling remaining order.'
                    );
                    $this->cancelRemainingMarketOrder($order);
                    break;
                }
            }

            $this->completeOrder($order, $oppositeOrder);
            $this->broadcastOrderBook($order->market_id);
        }

        // ---- Start: Added Logic for Partial/No Fill ----
        $order->refresh(); // Get the latest state after potential updates in completeOrder

        $finalRemindedQuantity = $order->getRemindedQuantity();

        // Check if the order is still open and has remaining quantity
        if ($order->status === SpotOrderStatusEnum::OPEN && Math::comp($finalRemindedQuantity, 0) === 1) {
            if (Math::comp($finalRemindedQuantity, $initialRemindedQuantity) === 0) {
                // Case 1: No fills occurred at all
                \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("Market order {$order->id} could not be filled. Canceling.");
                $this->cancelRemainingMarketOrder($order);
            } elseif (Math::comp($finalRemindedQuantity, $initialRemindedQuantity) === -1) {
                // Case 2: Partially filled
                \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("Market order {$order->id} was partially filled. Canceling remaining quantity: {$finalRemindedQuantity}.");
                $this->cancelRemainingMarketOrder($order);
            }
            // If $finalRemindedQuantity is somehow greater than initial, it's an error state.
            // If $finalRemindedQuantity is zero or less, it was fully filled, do nothing here.
        }
        // ---- End: Added Logic for Partial/No Fill ----
    }

    public function limit(SpotOrder $order): void
    {
        $oppositeType = $order->side === SpotOrderSideEnum::BUY ? SpotOrderSideEnum::SELL : SpotOrderSideEnum::BUY;
        $sortType = $order->side === SpotOrderSideEnum::BUY ? 'ASC' : 'DESC';

        // -------------------------------------------------------------
        // Price Deviation Protection for LIMIT orders
        // Goal: Allow user to place any price but prevent an extremely
        //       outlier price (far beyond current best opposite price)
        //       from immediately matching against the book.
        //       Instead, such an order should be posted untouched.
        // Config: setting key 'spot_limit_max_deviation_percent'
        //   - If not set or zero/negative -> feature disabled (legacy behavior)
        //   - For BUY: if order.price > bestAsk * (1 + deviation%) skip matching
        //   - For SELL: if order.price < bestBid * (1 - deviation%) skip matching
        // -------------------------------------------------------------

        // Read from config with default 10%
        $maxDeviationPercent = (float) config('spot.spot_limit_max_deviation_percent', 10);

        if ($maxDeviationPercent > 0 && $order->price !== null) {
            // Fetch best opposite price from DB (existing open orders)
            $dbBestOpposite = SpotOrder::query()
                ->where('side', $oppositeType)
                ->where('market_id', $order->market_id)
                ->where('status', SpotOrderStatusEnum::OPEN)
                ->where('user_id', '!=', $order->user_id)
                ->whereNotNull('price')
                ->orderBy('price', $order->side === SpotOrderSideEnum::BUY ? 'asc' : 'desc')
                ->value('price');

            // Fetch best opposite price from in-memory (bot) orders
            $inMemoryOpposites = $this->inMemoryOrderBook->getMarketOrders($order->market_id, $oppositeType, 100);
            $inMemoryBestOpposite = null;
            foreach ($inMemoryOpposites as $memOrder) {
                if ($memOrder->price === null) {
                    continue; // skip market bot orders for deviation calc
                }
                if ($inMemoryBestOpposite === null) {
                    $inMemoryBestOpposite = $memOrder->price;
                } else {
                    $comp = Math::comp($memOrder->price, $inMemoryBestOpposite);
                    // For BUY we want lowest ask, for SELL we want highest bid
                    if ($order->side === SpotOrderSideEnum::BUY) {
                        if ($comp === -1) { // mem price < current best
                            $inMemoryBestOpposite = $memOrder->price;
                        }
                    } else { // SELL -> choose highest bid
                        if ($comp === 1) { // mem price > current best
                            $inMemoryBestOpposite = $memOrder->price;
                        }
                    }
                }
            }

            // Determine final best opposite price considering both sources
            $bestOppositePrice = $dbBestOpposite;
            if ($inMemoryBestOpposite !== null) {
                if ($bestOppositePrice === null) {
                    $bestOppositePrice = $inMemoryBestOpposite;
                } else {
                    $comp = Math::comp($inMemoryBestOpposite, $bestOppositePrice);
                    if ($order->side === SpotOrderSideEnum::BUY) {
                        if ($comp === -1) { // in-memory ask lower
                            $bestOppositePrice = $inMemoryBestOpposite;
                        }
                    } else { // SELL
                        if ($comp === 1) { // in-memory bid higher
                            $bestOppositePrice = $inMemoryBestOpposite;
                        }
                    }
                }
            }

            if ($bestOppositePrice !== null) {
                // Calculate deviation percent
                if ($order->side === SpotOrderSideEnum::BUY) {
                    // Order price above best ask
                    $priceDiff = Math::sub($order->price, $bestOppositePrice);
                    if (Math::comp($priceDiff, 0) === 1) {
                        $deviationPercent = Math::mul(Math::div($priceDiff, $bestOppositePrice), 100);
                        if (Math::comp($deviationPercent, $maxDeviationPercent) === 1) {
                            Log::channel('spot-order-matching')->info('[LIMIT-PROTECTION] Skipping immediate match for BUY order '.$order->id.' price='.$order->price.' bestAsk='.$bestOppositePrice.' deviation='.$deviationPercent.'% > '.$maxDeviationPercent.'%');
                            return; // Post order without matching
                        }
                    }
                } else { // SELL
                    // Order price below best bid
                    $priceDiff = Math::sub($bestOppositePrice, $order->price);
                    if (Math::comp($priceDiff, 0) === 1) {
                        $deviationPercent = Math::mul(Math::div($priceDiff, $bestOppositePrice), 100);
                        if (Math::comp($deviationPercent, $maxDeviationPercent) === 1) {
                            Log::channel('spot-order-matching')->info('[LIMIT-PROTECTION] Skipping immediate match for SELL order '.$order->id.' price='.$order->price.' bestBid='.$bestOppositePrice.' deviation='.$deviationPercent.'% > '.$maxDeviationPercent.'%');
                            return; // Post order without matching
                        }
                    }
                }
            }
        }

        // Get opposite orders from both database and Redis (bot orders)
        $oppositeOrdersData = $this->getOppositeOrdersForLimit($order, $oppositeType, $sortType);

        foreach ($oppositeOrdersData as $oppositeOrderData) {
            if ($order->getRemindedQuantity() <= 0) {
                break;
            }

            // Convert to SpotOrder model (if bot order, will be persisted to DB)
            $oppositeOrder = $this->ensureSpotOrderModel($oppositeOrderData);

            // If matching with a market order, set its price to the limit order's price
            if ($oppositeOrder->price === null) {
                $oppositeOrder->price = $order->price;
            }

            $this->completeOrder($order, $oppositeOrder);
            $this->broadcastOrderBook($order->market_id);
            if ($order->status === SpotOrderStatusEnum::COMPLETED) {
                $this->broadcastUserNotifications($order->user_id);
            }
            if ($oppositeOrder->status === SpotOrderStatusEnum::COMPLETED) {
                $this->broadcastUserNotifications($oppositeOrder->user_id);
            }
        }
    }

    private function completeOrder(SpotOrder $order, SpotOrder $oppositeOrder): void
    {
        $spotMakerFee = Setting::getSetting('spot_maker_fee');
        $spotTakerFee = Setting::getSetting('spot_taker_fee');
        $tradeQuantity = Math::comp($order->getRemindedQuantity(), $oppositeOrder->getRemindedQuantity()) <= 0
            ? $order->getRemindedQuantity()
            : $oppositeOrder->getRemindedQuantity();

        $order->update(['filled_quantity' => Math::add($order->filled_quantity, $tradeQuantity)]);
        $oppositeOrder->update(['filled_quantity' => Math::add($oppositeOrder->filled_quantity, $tradeQuantity)]);

        // **Detect Maker & Taker**
        $takerOrder = $order; // Incoming order is the taker
        $makerOrder = $oppositeOrder; // Existing order in the book is the maker

        $spotTrade = SpotTrade::query()
            ->create([
                'maker_order_id' => $makerOrder->id,
                'taker_order_id' => $takerOrder->id,
                'quantity' => $tradeQuantity,
                'price' => $makerOrder->price,
                'market_id' => $order->market_id,
            ]);

        // Calculate trade cost (in quote currency)
        $tradeCost = Math::mul($tradeQuantity, $makerOrder->price);

        // Calculate commission based on what each party receives
        // If maker is buying, they receive base currency, otherwise quote currency

        if ($makerOrder->side === SpotOrderSideEnum::BUY) {
            // Maker buys and receives base currency (e.g., TRX)
            $makerCommissionAmount = $this->calcCommission($tradeQuantity, Math::div($spotMakerFee, 100));
            $makerCommissionCurrency = $order->market->base_currency;
        } else {
            // Maker sells and receives quote currency (e.g., USDT)
            $makerCommissionAmount = $this->calcCommission($tradeCost, Math::div($spotMakerFee, 100));
            $makerCommissionCurrency = $order->market->quote_currency;
        }

        // If taker is buying, they receive base currency, otherwise quote currency
        if ($takerOrder->side === SpotOrderSideEnum::BUY) {
            // Taker buys and receives base currency (e.g., TRX)
            $takerCommissionAmount = $this->calcCommission($tradeQuantity, Math::div($spotTakerFee, 100));
            $takerCommissionCurrency = $order->market->base_currency;
        } else {
            // Taker sells and receives quote currency (e.g., USDT)
            $takerCommissionAmount = $this->calcCommission($tradeCost, Math::div($spotTakerFee, 100));
            $takerCommissionCurrency = $order->market->quote_currency;
        }

        TradingCommission::query()
            ->create([
                'spot_trade_id' => $spotTrade->id,
                'maker_commission_amount' => $makerCommissionAmount,
                'maker_commission_percentage' => $spotMakerFee,
                'maker_commission_currency' => $makerCommissionCurrency,
                'taker_commission_amount' => $takerCommissionAmount,
                'taker_commission_percentage' => $spotTakerFee,
                'taker_commission_currency' => $takerCommissionCurrency,
            ]);


        $this->addTransactions($order, $spotTrade, $makerCommissionAmount, $takerCommissionAmount);
        $this->addTransactions($oppositeOrder, $spotTrade, $makerCommissionAmount, $takerCommissionAmount);

        $this->updateWallets($takerOrder, $makerOrder, $tradeQuantity, $makerOrder->price, $makerCommissionAmount, $takerCommissionAmount);

        
        // Dispatch ref exchange sell if user is selling to bot (bot is buying)
        $this->dispatchRefExchangeSellIfBotBuying($takerOrder, $makerOrder, $spotTrade, $tradeQuantity);

        if ($order->getRemindedQuantity() <= 0) {
            // $order->price = $order->getOriginal('price'); // REMOVE THIS for market orders
            if ($order->type !== SpotOrderTypeEnum::MARKET) { // Only reset price for limit orders
                $order->price = $order->getOriginal('price');
            }
            $order->update(['status' => SpotOrderStatusEnum::COMPLETED]);

            // Update description before deleting locked balance for completed order
            $lockedDetail = LockedBalanceDetail::query()->where('spot_order_id', $order->id)->first();
            if ($lockedDetail) {
                $description = "تکمیل سفارش اسپات #{$order->id} - پر شده: {$order->filled_quantity}";
                $lockedDetail->update(['description' => $description]);
            }

            LockedBalanceDetail::query()
                ->where('spot_order_id', $order->id)
                ->delete();
            // Broadcast user notification for the completed order
            $this->broadcastUserNotifications($order->user_id);

            // --- شروع منطق جدید برای سفارش مارکت ---
            if ($order->type === SpotOrderTypeEnum::MARKET) {
                // محاسبه میانگین قیمت تمام معاملات انجام‌شده برای این سفارش
                $avgPrice = \App\Models\SpotTrade::query()
                    ->where('taker_order_id', $order->id)
                    ->avg('price');
                if ($avgPrice !== null) {
                    $order->update(['price' => $avgPrice]);
                }
            }
            // --- پایان منطق جدید ---
        }

        if ($oppositeOrder->getRemindedQuantity() <= 0) {
            // $oppositeOrder->price = $oppositeOrder->getOriginal('price'); // REMOVE THIS if opposite is market
            if ($oppositeOrder->type !== SpotOrderTypeEnum::MARKET) { // Only reset price for limit orders
                $oppositeOrder->price = $oppositeOrder->getOriginal('price');
            }
            $oppositeOrder->update(['status' => SpotOrderStatusEnum::COMPLETED]);

            // Update description before deleting locked balance for completed opposite order
            $oppositeLockedDetail = LockedBalanceDetail::query()->where('spot_order_id', $oppositeOrder->id)->first();
            if ($oppositeLockedDetail) {
                $description = "تکمیل سفارش اسپات #{$oppositeOrder->id} - پر شده: {$oppositeOrder->filled_quantity}";
                $oppositeLockedDetail->update(['description' => $description]);
            }

            LockedBalanceDetail::query()
                ->where('spot_order_id', $oppositeOrder->id)
                ->delete();
            // Broadcast user notification for the completed opposite order
            $this->broadcastUserNotifications($oppositeOrder->user_id);
        }
    }

    private function addTransactions(SpotOrder $spotOrder, SpotTrade $spotTrade, string $makerCommissionAmount, string $takerCommissionAmount): void
    {
        // ارزهای مارکت را مستقیم از market بگیر (نباید بر اساس side تغییر کنند)
        $baseCurrency = $spotOrder->market->base_currency;   // مثلا ETH
        $quoteCurrency = $spotOrder->market->quote_currency; // مثلا USDT

        $walletBaseCurrency = $this->walletRepository->getOrCreateWallet($spotOrder->user_id, $baseCurrency);
        $walletQuoteCurrency = $this->walletRepository->getOrCreateWallet($spotOrder->user_id, $quoteCurrency);

        // تعیین چه چیزی دریافت و چه چیزی پرداخت میشه
        if ($spotOrder->side === SpotOrderSideEnum::BUY) {
            // خرید: دریافت base (ETH)، پرداخت quote (USDT)
            $receiveAmount = $spotTrade->quantity; // ETH
            $payAmount = Math::mul($spotTrade->quantity, $spotTrade->price); // USDT
            $receiveWallet = $walletBaseCurrency;
            $payWallet = $walletQuoteCurrency;
        } else {
            // فروش: دریافت quote (USDT)، پرداخت base (ETH)
            $receiveAmount = Math::mul($spotTrade->quantity, $spotTrade->price); // USDT
            $payAmount = $spotTrade->quantity; // ETH
            $receiveWallet = $walletQuoteCurrency;
            $payWallet = $walletBaseCurrency;
        }

        // Capture موجودی‌های اولیه قبل از هر تراکنش
        $initialReceiveBalance = $receiveWallet->balance;
        $initialPayBalance = $payWallet->balance;

        // تراکنش دریافت (BUY) - با موجودی اولیه
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::BUY)
                ->setAmount($receiveAmount)
                ->setCoinPrice($spotOrder->side === SpotOrderSideEnum::BUY ? $spotTrade->price : "1")
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($initialReceiveBalance)
                ->setDescription(
                    sprintf(
                        '%s %s %s',
                        $spotOrder->side === SpotOrderSideEnum::BUY ? 'خرید' : 'فروش',
                        formatNumberTrimZeros((float)$spotTrade->quantity),
                        $spotOrder->market->base_currency
                    )
                )
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($receiveWallet->id)
        );

        // محاسبه موجودی بعد از دریافت
        $balanceAfterReceive = Math::add($initialReceiveBalance, $receiveAmount);

        // تراکنش پرداخت (SELL) - با موجودی اولیه
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::SELL)
                ->setAmount(Math::mul($payAmount, '-1'))
                ->setCoinPrice($spotOrder->side === SpotOrderSideEnum::BUY ? "1" : $spotTrade->price)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($initialPayBalance)
                ->setDescription(
                    sprintf(
                        '%s %s %s',
                        $spotOrder->side === SpotOrderSideEnum::BUY ? 'خرید' : 'فروش',
                        formatNumberTrimZeros((float)$spotTrade->quantity),
                        $spotOrder->market->base_currency
                    )
                )
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($payWallet->id)
        );

        // Determine which commission applies based on role
        if ($spotOrder->role === SpotOrderRoleEnum::MAKER) {
            $commissionAmount = $makerCommissionAmount;
        } else {
            $commissionAmount = $takerCommissionAmount;
        }

        // کارمزد از ارزی که دریافت میشه کسر میشه
        // برای BUY: از ETH کسر میشه
        // برای SELL: از USDT کسر میشه
        $commissionWallet = $receiveWallet;
        $commissionBalanceBeforeFee = $balanceAfterReceive; // موجودی بعد از دریافت، قبل از کسر کارمزد

        // تراکنش Commission - با موجودی بعد از تراکنش‌های دریافت/پرداخت
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::FEE)
                ->setAmount(Math::mul($commissionAmount, '-1'))
                ->setCoinPrice($spotOrder->side === SpotOrderSideEnum::BUY ? $spotTrade->price : "1")
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($commissionBalanceBeforeFee)
                ->setDescription(
                    sprintf(
                        'کارمزد معامله اسپات %s %s به ارزش %s',
                        $spotOrder->side === SpotOrderSideEnum::BUY ? 'خرید' : 'فروش',
                        $spotOrder->market->base_currency,
                        formatNumberTrimZeros((float)$commissionAmount),
                    )
                )
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($commissionWallet->id)
        );
    }

    public function calcCommission($tradeAmount, $fee = 0.001): string
    {
        return Math::mul($tradeAmount, $fee);
    }

    private function updateWallets(SpotOrder $takerOrder, SpotOrder $makerOrder, $tradeQuantity, $tradePrice, $makerCommission, $takerCommission): void
    {
        $baseCurrency = $takerOrder->market->base_currency; // Example: BTC
        $quoteCurrency = $takerOrder->market->quote_currency; // Example: USDT

        $actualTradeCost = Math::mul($tradeQuantity, $tradePrice);
        // $expectedTradeCost = Math::mul($tradeQuantity, $takerOrder->price); // <-- Line 361: Error occurs here if $takerOrder->price is null

        // **Taker Updates**
        if ($takerOrder->side === SpotOrderSideEnum::BUY) {
            // Buyer (Taker) receives base currency, pays in quote currency
            // Commission is taken from base currency (what they receive)

            $takerReceiveQty = Math::sub($tradeQuantity, $takerCommission);

            if ($takerOrder->type !== SpotOrderTypeEnum::MARKET && $takerOrder->source !== SpotOrderSourceEnum::BOT) {
                // Expected cost based on taker limit price for this executed quantity
                $expectedTradeCost = ($takerOrder->price !== null)
                    ? Math::mul($tradeQuantity, $takerOrder->price)
                    : $actualTradeCost;

                // Reduce wallet locked balance by the expected cost (release full lock for this fill)
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $expectedTradeCost);

                // Keep LockedBalanceDetail in sync for BUY taker orders during partial fills
                $lockedDetail = \App\Models\LockedBalanceDetail::query()
                    ->where('spot_order_id', $takerOrder->id)
                    ->first();
                if ($lockedDetail) {
                    $lockedDetail->amount = \App\Helpers\Math::sub($lockedDetail->amount, $expectedTradeCost);
                    $lockedDetail->save();
                }
            }

            $this->walletRepository->increaseBalance($takerOrder->user_id, $baseCurrency, $takerReceiveQty);

            //Decrease Quote Currency
            $this->walletRepository->decreaseBalance($takerOrder->user_id, $quoteCurrency, $actualTradeCost);

            // Refund logic removed: we already released the full expected lock for this fill above.
        } else {
            // Seller (Taker) receives quote currency, pays in base currency
            // Commission is taken from quote currency (what they receive)
            $takerReceiveQuote = Math::sub($actualTradeCost, $takerCommission);

            if ($takerOrder->type !== SpotOrderTypeEnum::MARKET && $takerOrder->source !== SpotOrderSourceEnum::BOT) {
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $baseCurrency, $tradeQuantity);

                // Update LockedBalanceDetail for SELL orders during partial matches
                $lockedDetail = LockedBalanceDetail::query()->where('spot_order_id', $takerOrder->id)->first();
                if ($lockedDetail) {
                    $lockedDetail->amount = Math::sub($lockedDetail->amount, $tradeQuantity);
                    $lockedDetail->save();
                }
            }


            $this->walletRepository->increaseBalance($takerOrder->user_id, $quoteCurrency, $takerReceiveQuote);

            //Decrease Base Currency balance (what they sold)
            $this->walletRepository->decreaseBalance($takerOrder->user_id, $baseCurrency, $tradeQuantity);
        }

        // **Maker Updates**
        if ($makerOrder->side === SpotOrderSideEnum::BUY) {
            // Buyer (Maker) receives base currency, pays in quote currency
            // Commission is taken from base currency (what they receive)
            $makerReceiveQty = Math::sub($tradeQuantity, $makerCommission);

            //increase Receive Coin Balance
            $this->walletRepository->increaseBalance($makerOrder->user_id, $baseCurrency, $makerReceiveQty);

            //Decrease Quote Currency
            $this->walletRepository->decreaseBalance($makerOrder->user_id, $quoteCurrency, $actualTradeCost);

            if ($makerOrder->source !== SpotOrderSourceEnum::BOT) {
                $this->walletRepository->decreaseLockedBalance($makerOrder->user_id, $quoteCurrency, $actualTradeCost);
            }

            // Update LockedBalanceDetail for BUY Maker orders during partial matches
            $lockedDetail = LockedBalanceDetail::query()->where('spot_order_id', $makerOrder->id)->first();
            if ($lockedDetail) {
                $lockedDetail->amount = Math::sub($lockedDetail->amount, $actualTradeCost);
                $lockedDetail->save();
            }
        } else {
            // Seller (Maker) receives quote currency, pays in base currency
            // Commission is taken from quote currency (what they receive)
            $makerReceiveQuote = Math::sub($actualTradeCost, $makerCommission);
            $this->walletRepository->increaseBalance($makerOrder->user_id, $quoteCurrency, $makerReceiveQuote);

            if ($makerOrder->source !== SpotOrderSourceEnum::BOT) {
                $this->walletRepository->decreaseLockedBalance($makerOrder->user_id, $baseCurrency, $tradeQuantity);
            }

            // Update LockedBalanceDetail for SELL Maker orders during partial matches
            $lockedDetail = LockedBalanceDetail::query()->where('spot_order_id', $makerOrder->id)->first();
            if ($lockedDetail) {
                $lockedDetail->amount = Math::sub($lockedDetail->amount, $tradeQuantity);
                $lockedDetail->save();
            }

            //Decrease Quote Currency
            $this->walletRepository->decreaseBalance($makerOrder->user_id, $baseCurrency, $tradeQuantity);
        }
    }

    private function broadcastOrderBook(int $marketId): void
    {
        // Funnel all broadcasts through the unique job to throttle & deduplicate
        BroadcastOrderBook::dispatch($marketId);
    }

    private function broadcastUserNotifications(int $userId): void
    {
        UserNotification::dispatch($userId, __('user_notifications.spot_order.order_completed', locale: 'fa'));
    }

    /**
     * Cancels the remaining part of an open market order.
     */
    private function cancelRemainingMarketOrder(SpotOrder $order): void
    {
        // Ensure it's still a market order and open before proceeding
        if ($order->type === SpotOrderTypeEnum::MARKET && $order->status === SpotOrderStatusEnum::OPEN) {
            DB::transaction(function () use ($order) {
                // Determine the correct cancellation status
                $initialQuantity = $order->getOriginal('quantity'); // Or however you store the initial total quantity
                $filledQuantity = $order->filled_quantity;
                $newStatus = SpotOrderStatusEnum::CANCELED; // Default to canceled

                // Check if any part of the order was filled before cancellation
                if (Math::comp($filledQuantity, 0) === 1 && Math::comp($filledQuantity, $initialQuantity) === -1) {
                    $newStatus = SpotOrderStatusEnum::PARTIALLY_FILLED_CANCELED;
                    \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("Market order {$order->id} was partially filled. Setting status to PARTIALLY_FILLED_CANCELED.");
                } else {
                    \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("Market order {$order->id} had no fills before cancellation. Setting status to CANCELED.");
                }

                $order->status = $newStatus; // Use the determined status
                $order->save();

                // Release remaining locked balance for non-bot orders only
                if ($order->source !== SpotOrderSourceEnum::BOT) {
                    $this->releaseRemainingLockedBalance($order);
                }

                // Update description before deleting locked balance for canceled market order (skip for bots)
                if ($order->source !== SpotOrderSourceEnum::BOT) {
                    $lockedDetail = LockedBalanceDetail::query()->where('spot_order_id', $order->id)->first();
                    if ($lockedDetail) {
                        $description = '';
                        if (Math::comp($filledQuantity, 0) !== 0) {
                            $description = "لغو سفارش مارکت #{$order->id} - پر شده: {$filledQuantity} از {$initialQuantity}";
                        } else {
                            $description = "لغو سفارش مارکت #{$order->id} - بدون پر شدن";
                        }
                        $lockedDetail->update(['description' => $description]);
                    }
                }

                // Clean up any potentially remaining locked balance detail entry (skip for bots)
                if ($order->source !== SpotOrderSourceEnum::BOT) {
                    LockedBalanceDetail::query()
                        ->where('spot_order_id', $order->id)
                        ->delete();
                }

                // Optionally notify the user about the cancellation
                // UserNotification::dispatch($order->user_id, __('user_notifications.spot_order.market_order_canceled', ['status' => $newStatus->value], locale: 'fa'));

            });
            // Update the order book after cancellation
            $this->broadcastOrderBook($order->market_id);
        } else {
            \Illuminate\Support\Facades\Log::channel('spot-order-matching')->warning("Attempted to cancel order {$order->id} which is not an open market order. Status: {$order->status->value}, Type: {$order->type->value}");
        }
    }

    /**
     * Releases the locked balance for the canceled portion of a market order.
     */
    private function releaseRemainingLockedBalance(SpotOrder $order): void
    {
        // Use refresh to ensure we have the latest quantity info
        $order->refresh();
        // Calculate remaining quantity based on initial and filled, NOT getRemindedQuantity() as status is changing
        $initialQuantity = $order->getOriginal('quantity');
        $filledQuantity = $order->filled_quantity;
        $remainedQuantity = Math::sub($initialQuantity, $filledQuantity);


        if (Math::comp($remainedQuantity, 0) <= 0) {
            \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("No remaining quantity ({$remainedQuantity}) to release balance for order {$order->id}. Initial: {$initialQuantity}, Filled: {$filledQuantity}");
            return; // Nothing to release
        }

        \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("Attempting to release balance for remaining quantity {$remainedQuantity} of order {$order->id}.");


        try {
            if ($order->side === SpotOrderSideEnum::BUY) {
                // Market Buy: Locked quote currency.
                // We need to know how much quote currency is *still* locked for the remaining UNFILLED quantity.
                // This is tricky without knowing the average fill price or the exact lock amount remaining.
                // Assuming LockedBalanceDetail holds the *total initial lock* or is updated precisely.
                // SAFER APPROACH: Recalculate the required lock for the *filled* amount and unlock the difference.

                // Let's assume LockedBalanceDetail holds the *current remaining* lock amount.
                // This requires LockedBalanceDetail to be updated during partial fills, which is complex.
                // If LockedBalanceDetail only holds the initial lock, this logic is incorrect.

                $lockedDetail = LockedBalanceDetail::query()->where('spot_order_id', $order->id)->first();
                if ($lockedDetail && Math::comp($lockedDetail->amount, 0) === 1) {
                    // WARNING: This assumes $lockedDetail->amount is the *remaining* lock, not the initial lock.
                    $amountToUnlock = $lockedDetail->amount;
                    // Decrease locked balance (which should increase available balance)
                    $this->walletRepository->decreaseLockedBalance($order->user_id, $order->market->quote_currency, $amountToUnlock);
                    \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("Released remaining locked quote balance for canceled market buy order {$order->id} based on LockedBalanceDetail. Amount: {$amountToUnlock}");
                } else {
                    // Fallback/Warning: If LockedBalanceDetail is missing or zero, or holds initial lock.
                    \Illuminate\Support\Facades\Log::channel('spot-order-matching')->error("Could not find valid/updated LockedBalanceDetail to release funds accurately for canceled market buy order {$order->id}. Remained quantity: {$remainedQuantity}. Manual check required or revise unlock logic.");
                    // !! Consider implementing a more robust unlock calculation based on filled amount/price if possible !!
                }
            } else {
                // Market Sell: For MARKET orders, no base currency was locked at creation.
                // Avoid decreasing locked_balance to prevent negative values on partial fill + cancel.
                if ($order->type !== SpotOrderTypeEnum::MARKET) {
                    // For non-market sells that had base locked, unlock the unfilled remainder.
                    $currencyToUnlock = $order->market->base_currency;
                    $amountToUnlock = $remainedQuantity; // quantity that was not filled
                    $this->walletRepository->decreaseLockedBalance($order->user_id, $currencyToUnlock, $amountToUnlock);
                    \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("Released remaining locked base balance for canceled non-market sell order {$order->id}. Amount: {$amountToUnlock}");
                } else {
                    // Nothing to unlock for market sells; balances were taken directly from available.
                    \Illuminate\Support\Facades\Log::channel('spot-order-matching')->info("No locked balance to release for canceled market sell order {$order->id}. Remaining quantity: {$remainedQuantity}");
                }
            }
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('spot-order-matching')->error("Failed to release locked balance for canceled order {$order->id}: " . $e->getMessage(), ['exception' => $e]);
            // Rethrow or handle appropriately - failing to unlock funds is critical.
            throw $e;
        }
    }

    /**
     * Get opposite orders from both database and Redis, prioritized by price
     * 
     * @param SpotOrder $order
     * @param SpotOrderSideEnum $oppositeType
     * @param string $sortType 'ASC' or 'DESC'
     * @return array Array of SpotOrder objects (bot orders converted to database)
     */
    private function getOppositeOrders(SpotOrder $order, SpotOrderSideEnum $oppositeType, string $sortType): array
    {
        // Get database orders
        $dbOrders = SpotOrder::query()
            ->where('side', $oppositeType)
            ->where('market_id', $order->market_id)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->where('user_id', '!=', $order->user_id) // Prevent self-trading
            ->whereNotNull('price')
            ->lockForUpdate()
            ->get();

        // Get Redis bot orders
        $inMemoryOrders = $this->inMemoryOrderBook->getMarketOrders(
            $order->market_id,
            $oppositeType,
            100
        );

        // Convert in-memory orders to database format and combine
        $allOrders = $dbOrders->toArray();

        foreach ($inMemoryOrders as $inMemoryOrder) {
            // Convert to database format for matching
            $allOrders[] = [
                'id' => $inMemoryOrder->id,
                'user_id' => $inMemoryOrder->user_id,
                'market_id' => $inMemoryOrder->market_id,
                'side' => $inMemoryOrder->side->value,
                'price' => $inMemoryOrder->price,
                'quantity' => $inMemoryOrder->quantity,
                'filled_quantity' => $inMemoryOrder->filled_quantity,
                'status' => $inMemoryOrder->status->value,
                'type' => $inMemoryOrder->type->value,
                'is_bot_order' => true, // Flag to identify bot orders
                'in_memory_order' => $inMemoryOrder, // Keep reference
            ];
        }

        // Sort all orders by price
        usort($allOrders, function ($a, $b) use ($sortType) {
            $priceA = is_array($a) ? $a['price'] : $a->price;
            $priceB = is_array($b) ? $b['price'] : $b->price;

            $comp = Math::comp($priceA, $priceB);

            return $sortType === 'ASC' ? $comp : -$comp;
        });

        return $allOrders;
    }

    /**
     * Get opposite orders for limit order matching (includes market orders)
     * 
     * @param SpotOrder $order
     * @param SpotOrderSideEnum $oppositeType
     * @param string $sortType
     * @return array
     */
    private function getOppositeOrdersForLimit(SpotOrder $order, SpotOrderSideEnum $oppositeType, string $sortType): array
    {
        // Get database orders (including market orders with price check)
        $dbOrders = SpotOrder::query()
            ->where('side', $oppositeType)
            ->where('market_id', $order->market_id)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->where('user_id', '!=', $order->user_id)
            ->where(function ($query) use ($order) {
                $query->whereNull('price') // Market order
                    ->orWhere(function ($q) use ($order) {
                        if ($order->side === SpotOrderSideEnum::BUY) {
                            $q->where('price', '<=', $order->price);
                        } else {
                            $q->where('price', '>=', $order->price);
                        }
                    });
            })
            ->orderByRaw('price IS NULL DESC') // Prioritize market orders (NULL price first)
            ->orderBy('price', $sortType === 'ASC' ? 'asc' : 'desc')
            ->lockForUpdate()
            ->get();

        // Get Redis bot orders (only limit orders with price check)
        $inMemoryOrders = $this->inMemoryOrderBook->getMarketOrders(
            $order->market_id,
            $oppositeType,
            100
        );

        // Filter in-memory orders by price condition
        $filteredInMemoryOrders = array_filter($inMemoryOrders, function ($inMemoryOrder) use ($order) {
            if ($order->side === SpotOrderSideEnum::BUY) {
                return Math::comp($inMemoryOrder->price, $order->price) <= 0;
            } else {
                return Math::comp($inMemoryOrder->price, $order->price) >= 0;
            }
        });

        // Combine orders
        $allOrders = $dbOrders->toArray();

        foreach ($filteredInMemoryOrders as $inMemoryOrder) {
            $allOrders[] = [
                'id' => $inMemoryOrder->id,
                'user_id' => $inMemoryOrder->user_id,
                'market_id' => $inMemoryOrder->market_id,
                'side' => $inMemoryOrder->side->value,
                'price' => $inMemoryOrder->price,
                'quantity' => $inMemoryOrder->quantity,
                'filled_quantity' => $inMemoryOrder->filled_quantity,
                'status' => $inMemoryOrder->status->value,
                'type' => $inMemoryOrder->type->value,
                'is_bot_order' => true,
                'in_memory_order' => $inMemoryOrder,
            ];
        }

        // Sort: market orders (null price) first, then by price
        usort($allOrders, function ($a, $b) use ($sortType) {
            $priceA = is_array($a) ? $a['price'] : $a->price;
            $priceB = is_array($b) ? $b['price'] : $b->price;

            // Market orders (null) should come first
            if ($priceA === null && $priceB !== null) return -1;
            if ($priceA !== null && $priceB === null) return 1;
            if ($priceA === null && $priceB === null) return 0;

            $comp = Math::comp($priceA, $priceB);
            return $sortType === 'ASC' ? $comp : -$comp;
        });

        return $allOrders;
    }

    /**
     * Convert array representation to SpotOrder model
     * For bot orders, persist to database first
     * 
     * @param array|SpotOrder $orderData
     * @return SpotOrder
     */
    private function ensureSpotOrderModel($orderData): SpotOrder
    {
        if ($orderData instanceof SpotOrder) {
            return $orderData;
        }

        // Check if it's a bot order that needs to be persisted
        if (isset($orderData['is_bot_order']) && $orderData['is_bot_order'] === true) {
            $inMemoryOrder = $orderData['in_memory_order'];

            // Persist to database
            $spotOrder = $this->persistenceService->persistOrder($inMemoryOrder);

            // Delete from Redis after persisting
            $this->inMemoryOrderBook->deleteOrder($inMemoryOrder->id);

            Log::channel('spot-bot')->info('Bot order persisted for matching', [
                'in_memory_id' => $inMemoryOrder->id,
                'database_id' => $spotOrder->id,
                'market_id' => $spotOrder->market_id,
                'side' => $spotOrder->side->value,
                'price' => $spotOrder->price,
            ]);

            return $spotOrder;
        }

        // If it's array but not a bot order, find existing database record
        if (isset($orderData['id'])) {
            return SpotOrder::find($orderData['id']);
        }

        throw new \Exception('Unable to convert order data to SpotOrder model');
    }

    /**
     * Dispatch a job to sell on reference exchange if a bot is buying in this trade.
     * This ensures that when a user sells to the bot, the equivalent coins are sold
     * on the reference exchange to maintain balance consistency.
     *
     * @param SpotOrder $takerOrder
     * @param SpotOrder $makerOrder
     * @param SpotTrade $spotTrade
     * @param string $tradeQuantity
     * @return void
     */
    private function dispatchRefExchangeSellIfBotBuying(
        SpotOrder $takerOrder,
        SpotOrder $makerOrder,
        SpotTrade $spotTrade,
        string $tradeQuantity
    ): void {
        // Check if ref exchange sell is enabled for this market
        $market = Market::find($spotTrade->market_id);
        if (!$market || !$market->ref_exchange_sell_enabled) {
            return;
        }

        // Determine if bot is involved and is buying (user is selling to bot)
        $botOrder = null;

        // Check if taker is bot and is buying
        if ($takerOrder->source === SpotOrderSourceEnum::BOT && $takerOrder->side === SpotOrderSideEnum::BUY) {
            $botOrder = $takerOrder;
        }

        // Check if maker is bot and is buying
        if ($makerOrder->source === SpotOrderSourceEnum::BOT && $makerOrder->side === SpotOrderSideEnum::BUY) {
            $botOrder = $makerOrder;
        }

        // If no bot is buying, do nothing
        if ($botOrder === null) {
            return;
        }

        // Truncate to the base currency's actual precision to eliminate floating-point noise
        $precision = $market->baseCurrency?->amount_precision ?? 8;
        $quantity = bcadd($tradeQuantity, '0', $precision);

        // Skip if quantity is below the market's minimum trade amount
        $minSellQuantity = $market->min_trade_amount ?? '0';
        if (Math::comp($quantity, '0') <= 0) {
            Log::channel('spot-ref-exchange')->warning('Ref exchange sell skipped: quantity is zero after truncation', [
                'spot_trade_id' => $spotTrade->id,
                'market_id' => $spotTrade->market_id,
                'original_quantity' => $tradeQuantity,
            ]);
            return;
        }

        if (Math::comp($minSellQuantity, '0') > 0 && Math::comp($quantity, $minSellQuantity) < 0) {
            Log::channel('spot-ref-exchange')->warning('Ref exchange sell skipped: quantity below minimum', [
                'spot_trade_id' => $spotTrade->id,
                'market_id' => $spotTrade->market_id,
                'quantity' => $quantity,
                'min_sell_quantity' => $minSellQuantity,
            ]);
            return;
        }

        // Dispatch the job to sell on reference exchange
        Log::channel('spot-ref-exchange')->info('User selling to bot in spot trade, dispatching ref exchange sell job', [
            'spot_trade_id' => $spotTrade->id,
            'market_id' => $spotTrade->market_id,
            'quantity' => $quantity,
            'bot_order_id' => $botOrder->id,
            'bot_side' => $botOrder->side->value,
        ]);

        $spotTrade->update(['ref_exchange_sell_status' => RefExchangeSellStatusEnum::PENDING]);

        SellOnRefExchangeForSpotTrade::dispatch(
            $spotTrade->id,
            $spotTrade->market_id,
            $quantity
        );
    }
}
