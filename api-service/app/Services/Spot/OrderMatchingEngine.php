<?php

namespace App\Services\Spot;

use App\Enums\SpotOrderRoleEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\UserNotification;
use App\Jobs\BroadcastOrderBook;
use App\Helpers\Math;
use App\Models\LockedBalanceDetail;
use App\Models\Setting;
use App\Models\SpotOrder;
use App\Models\SpotTrade;
use App\Models\TradingCommission;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class OrderMatchingEngine
{
    public function __construct(
        private WalletRepositoryInterface               $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
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
                logger()->error('Order matching failed: ' . $e->getMessage(), ['exception' => $e]);
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

        $oppositeOrders = SpotOrder::query()
            ->where('side', $oppositeType)
            ->where('market_id', $order->market_id)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->where('user_id', '!=', $order->user_id) // Prevent self-trading
            ->orderBy('price', $sortType)
            ->whereNotNull('price') // Ensure matching against LIMIT orders only
            ->lockForUpdate()
            ->get();

        foreach ($oppositeOrders as $oppositeOrder) {
            // Double check reminded quantity within the loop
            if ($order->getRemindedQuantity(true) <= 0) { // Use fresh value
                break;
            }
            // Price is determined by the matched limit order (maker)
            // No need to set $order->price here, completeOrder uses $makerOrder->price

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
                logger()->info("Market order {$order->id} could not be filled. Canceling.");
                $this->cancelRemainingMarketOrder($order);
            } elseif (Math::comp($finalRemindedQuantity, $initialRemindedQuantity) === -1) {
                // Case 2: Partially filled
                logger()->info("Market order {$order->id} was partially filled. Canceling remaining quantity: {$finalRemindedQuantity}.");
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

        $oppositeOrders = SpotOrder::query()
            ->where('side', $oppositeType)
            ->where('market_id', $order->market_id)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->where('user_id', '!=', $order->user_id) // Prevent self-trading
            ->where(function ($query) use ($order) {
                // Match with:
                // - Market orders (no price)
                // - Limit orders that meet the price condition
                $query->whereNull('price') // Market order
                    ->orWhere(function ($q) use ($order) {
                        if ($order->side === SpotOrderSideEnum::BUY) {
                            $q->where('price', '<=', $order->price); // Buy: Match at or below limit price
                        } else {
                            $q->where('price', '>=', $order->price); // Sell: Match at or above limit price
                        }
                    });
            })
            ->orderByRaw('price IS NULL DESC') // Prioritize market orders (NULL price first)
            ->orderBy('price', $sortType) // Then sort limit orders by price
            ->lockForUpdate()
            ->get();

        foreach ($oppositeOrders as $oppositeOrder) {
            if ($order->getRemindedQuantity() <= 0) {
                break;
            }

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
        $tradeQuantity = min($order->getRemindedQuantity(), $oppositeOrder->getRemindedQuantity());

        $order->increment('filled_quantity', $tradeQuantity);
        $oppositeOrder->increment('filled_quantity', $tradeQuantity);

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

        $this->updateWallets($takerOrder, $makerOrder, $tradeQuantity, $makerOrder->price, $makerCommissionAmount, $takerCommissionAmount);

        $this->addTransactions($order, $spotTrade, $makerCommissionAmount, $takerCommissionAmount);
        $this->addTransactions($oppositeOrder, $spotTrade, $makerCommissionAmount, $takerCommissionAmount);

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
        $baseCurrency = $spotOrder->side === SpotOrderSideEnum::BUY ? $spotOrder->market->base_currency : $spotOrder->market->quote_currency;
        $quoteCurrency = $spotOrder->side === SpotOrderSideEnum::BUY ? $spotOrder->market->quote_currency : $spotOrder->market->base_currency;
        $walletBaseCurrency = $this->walletRepository->getOrCreateWallet($spotOrder->user_id, $baseCurrency);
        $walletQuoteCurrency = $this->walletRepository->getOrCreateWallet($spotOrder->user_id, $quoteCurrency,);

        $buyQuantity = $spotOrder->side === SpotOrderSideEnum::BUY ? $spotTrade->quantity : Math::mul($spotTrade->quantity, $spotTrade->price);
        $sellQuantity = $spotOrder->side === SpotOrderSideEnum::BUY ? Math::mul($spotTrade->quantity, $spotTrade->price) : $spotTrade->quantity;


        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::BUY)
                ->setAmount($buyQuantity)
                ->setCoinPrice($spotOrder->side === SpotOrderSideEnum::BUY ? $spotTrade->price : "1")
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($walletBaseCurrency->balance)
                ->setDescription(
                    sprintf(
                        '%s %s %s',
                        $spotOrder->side === SpotOrderSideEnum::BUY ? 'خرید' : 'فروش',
                        formatNumberTrimZeros((float)$spotTrade->quantity),
                        $spotOrder->market->base_currency
                    )
                )
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($walletBaseCurrency->id)
        );


        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::SELL)
                ->setAmount($sellQuantity * -1)
                ->setCoinPrice($spotOrder->side === SpotOrderSideEnum::BUY ? "1" : $spotTrade->price)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($walletQuoteCurrency->balance)
                ->setDescription(
                    sprintf(
                        '%s %s %s',
                        $spotOrder->side === SpotOrderSideEnum::BUY ? 'خرید' : 'فروش',
                        formatNumberTrimZeros((float)$spotTrade->quantity),
                        $spotOrder->market->base_currency
                    )
                )
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($walletQuoteCurrency->id)
        );

        // Determine which commission applies and which wallet to use based on role

        if ($spotOrder->role === SpotOrderRoleEnum::MAKER) {
            $commissionAmount = $makerCommissionAmount;
        } else {
            $commissionAmount = $takerCommissionAmount;
        }


        if ($spotOrder->side === SpotOrderSideEnum::BUY) {
            $commissionWallet = $this->walletRepository->getOrCreateWallet($spotOrder->user_id, $spotOrder->market->base_currency);
        } else {
            $commissionWallet = $this->walletRepository->getOrCreateWallet($spotOrder->user_id, $spotOrder->market->quote_currency);
        }

        // **Create Transaction for Commission**
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::FEE)
                ->setAmount($commissionAmount * -1)
                ->setCoinPrice($spotOrder->side === SpotOrderSideEnum::BUY ? $spotTrade->price : "1")
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($commissionWallet->balance)
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

            if ($takerOrder->type !== SpotOrderTypeEnum::MARKET) {
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $actualTradeCost);
            }

            $this->walletRepository->increaseBalance($takerOrder->user_id, $baseCurrency, $takerReceiveQty);

            //Decrease Quote Currency
            $this->walletRepository->decreaseBalance($takerOrder->user_id, $quoteCurrency, $actualTradeCost);

            // **Refund remaining USDT if price was lower than expected**
            // This logic only applies if the taker was a LIMIT order with a specific price
            if ($takerOrder->type === SpotOrderTypeEnum::LIMIT && $takerOrder->price !== null) {
                $expectedTradeCost = Math::mul($tradeQuantity, $takerOrder->price); // Calculate expected cost only for limit orders
                $refundAmount = Math::sub($expectedTradeCost, $actualTradeCost);
                if (Math::comp($refundAmount, 0) === 1) {
                    $this->walletRepository->increaseBalance($takerOrder->user_id, $quoteCurrency, $refundAmount);
                    // Also decrease the lock for the refunded amount, as it was initially locked based on expected cost
                    $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $refundAmount);
                    logger()->info("Refunded {$refundAmount} {$quoteCurrency} to user {$takerOrder->user_id} for taker order {$takerOrder->id} due to better execution price.");
                }
            }
        } else {
            // Seller (Taker) receives quote currency, pays in base currency
            // Commission is taken from quote currency (what they receive)
            $takerReceiveQuote = Math::sub($actualTradeCost, $takerCommission);

            if ($takerOrder->type !== SpotOrderTypeEnum::MARKET) {
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $baseCurrency, $tradeQuantity);
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

            $this->walletRepository->decreaseLockedBalance($makerOrder->user_id, $quoteCurrency, $actualTradeCost);
        } else {
            // Seller (Maker) receives quote currency, pays in base currency
            // Commission is taken from quote currency (what they receive)
            $makerReceiveQuote = Math::sub($actualTradeCost, $makerCommission);
            $this->walletRepository->increaseBalance($makerOrder->user_id, $quoteCurrency, $makerReceiveQuote);

            $this->walletRepository->decreaseLockedBalance($makerOrder->user_id, $baseCurrency, $tradeQuantity);

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
                    logger()->info("Market order {$order->id} was partially filled. Setting status to PARTIALLY_FILLED_CANCELED.");
                } else {
                    logger()->info("Market order {$order->id} had no fills before cancellation. Setting status to CANCELED.");
                }

                $order->status = $newStatus; // Use the determined status
                $order->save();

                // Release any remaining locked balance associated with the canceled portion
                $this->releaseRemainingLockedBalance($order);

                // Update description before deleting locked balance for canceled market order
                $lockedDetail = LockedBalanceDetail::query()->where('spot_order_id', $order->id)->first();
                if ($lockedDetail) {
                    $description = '';
                    if (Math::comp($filledQuantity, 0) !== 0) {
                        // Partially filled market order
                        $description = "لغو سفارش مارکت #{$order->id} - پر شده: {$filledQuantity} از {$initialQuantity}";
                    } else {
                        // Fully unfilled market order
                        $description = "لغو سفارش مارکت #{$order->id} - بدون پر شدن";
                    }
                    $lockedDetail->update(['description' => $description]);
                }

                // Clean up any potentially remaining locked balance detail entry
                LockedBalanceDetail::query()
                    ->where('spot_order_id', $order->id)
                    ->delete();

                // Optionally notify the user about the cancellation
                // UserNotification::dispatch($order->user_id, __('user_notifications.spot_order.market_order_canceled', ['status' => $newStatus->value], locale: 'fa'));

            });
            // Update the order book after cancellation
            $this->broadcastOrderBook($order->market_id);
        } else {
            logger()->warning("Attempted to cancel order {$order->id} which is not an open market order. Status: {$order->status->value}, Type: {$order->type->value}");
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
            logger()->info("No remaining quantity ({$remainedQuantity}) to release balance for order {$order->id}. Initial: {$initialQuantity}, Filled: {$filledQuantity}");
            return; // Nothing to release
        }

        logger()->info("Attempting to release balance for remaining quantity {$remainedQuantity} of order {$order->id}.");


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
                    logger()->info("Released remaining locked quote balance for canceled market buy order {$order->id} based on LockedBalanceDetail. Amount: {$amountToUnlock}");
                } else {
                    // Fallback/Warning: If LockedBalanceDetail is missing or zero, or holds initial lock.
                    logger()->error("Could not find valid/updated LockedBalanceDetail to release funds accurately for canceled market buy order {$order->id}. Remained quantity: {$remainedQuantity}. Manual check required or revise unlock logic.");
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
                    logger()->info("Released remaining locked base balance for canceled non-market sell order {$order->id}. Amount: {$amountToUnlock}");
                } else {
                    // Nothing to unlock for market sells; balances were taken directly from available.
                    logger()->info("No locked balance to release for canceled market sell order {$order->id}. Remaining quantity: {$remainedQuantity}");
                }
            }
        } catch (Throwable $e) {
            logger()->error("Failed to release locked balance for canceled order {$order->id}: " . $e->getMessage(), ['exception' => $e]);
            // Rethrow or handle appropriately - failing to unlock funds is critical.
            throw $e;
        }
    }
}
