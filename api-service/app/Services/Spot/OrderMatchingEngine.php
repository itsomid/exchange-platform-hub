<?php

namespace App\Services\Spot;

use App\Enums\SpotOrderRoleEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\OrderBookUpdated;
use App\Events\UserNotification;
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
    )
    {
    }

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

        $oppositeOrders = SpotOrder::query()
            ->where('side', $oppositeType)
            ->where('market_id', $order->market_id)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->orderBy('price', $sortType)
            ->whereNotNull('price') // Ensure matching against LIMIT orders only
            ->lockForUpdate()
            ->get();

        foreach ($oppositeOrders as $oppositeOrder) {
            if ($order->getRemindedQuantity() <= 0) {
                break;
            }
            // Set market order price to the matched limit order's price
            $order->price = $oppositeOrder->price;

            $this->completeOrder($order, $oppositeOrder);
            $this->broadcastOrderBook($order->market_id);
        }
    }

    public function limit(SpotOrder $order): void
    {
        $oppositeType = $order->side === SpotOrderSideEnum::BUY ? SpotOrderSideEnum::SELL : SpotOrderSideEnum::BUY;
        $sortType = $order->side === SpotOrderSideEnum::BUY ? 'ASC' : 'DESC';

        $oppositeOrders = SpotOrder::query()
            ->where('side', $oppositeType)
            ->where('market_id', $order->market_id)
            ->where('status', SpotOrderStatusEnum::OPEN)
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
        $makerCommissionAmount = null;
        $makerCommissionCurrency = null;
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
        $takerCommissionAmount = null;
        $takerCommissionCurrency = null;
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
            $order->price = $order->getOriginal('price');
            $order->update(['status' => SpotOrderStatusEnum::COMPLETED]);
            LockedBalanceDetail::query()
                ->where('spot_order_id', $order->id)
                ->delete();
        }

        if ($oppositeOrder->getRemindedQuantity() <= 0) {
            $oppositeOrder->price = $oppositeOrder->getOriginal('price');
            $oppositeOrder->update(['status' => SpotOrderStatusEnum::COMPLETED]);
            LockedBalanceDetail::query()
                ->where('spot_order_id', $oppositeOrder->id)
                ->delete();
        }
    }

    private function addTransactions(SpotOrder $spotOrder, SpotTrade $spotTrade, string $makerCommissionAmount, string $takerCommissionAmount): void
    {
        $baseCurrency = $spotOrder->side === SpotOrderSideEnum::BUY ? $spotOrder->market->base_currency : $spotOrder->market->quote_currency;
        $quoteCurrency = $spotOrder->side === SpotOrderSideEnum::BUY ? $spotOrder->market->quote_currency : $spotOrder->market->base_currency;
        $walletBaseCurrency = $this->walletRepository->getOneByCurrency($baseCurrency, $spotOrder->user_id);
        $walletQuoteCurrency = $this->walletRepository->getOneByCurrency($quoteCurrency, $spotOrder->user_id);

        $buyQuantity = $spotOrder->side === SpotOrderSideEnum::BUY ? $spotTrade->quantity : Math::mul($spotTrade->quantity, $spotTrade->price);
        $sellQuantity = $spotOrder->side === SpotOrderSideEnum::BUY ? Math::mul($spotTrade->quantity, $spotTrade->price) : $spotTrade->quantity;

        // **Create Transaction for Trader**
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::BUY)
                ->setAmount($buyQuantity)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($walletBaseCurrency->balance)
                ->setDescription(
                    sprintf('%s %s %s',
                        $spotOrder->side === SpotOrderSideEnum::BUY ? 'خرید' : 'فروش',
                        formatNumberTrimZeros((float)$spotTrade->quantity),
                        $spotOrder->market->base_currency
                    ))
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($walletBaseCurrency->id)
        );

        // **Create Transaction for Trader**
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::SELL)
                ->setAmount($sellQuantity * -1)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($walletQuoteCurrency->balance)
                ->setDescription(
                    sprintf('%s %s %s',
                        $spotOrder->side === SpotOrderSideEnum::BUY ? 'خرید' : 'فروش',
                        formatNumberTrimZeros((float)$spotTrade->quantity),
                        $spotOrder->market->base_currency
                    ))
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($walletQuoteCurrency->id)
        );

        // Determine which commission applies and which wallet to use based on role
        $commissionAmount = null;
        $commissionWallet = null;

        if ($spotOrder->role === SpotOrderRoleEnum::MAKER) {
            $commissionAmount = $makerCommissionAmount;
            // If this is the maker's transaction
            if ($spotOrder->side === SpotOrderSideEnum::BUY) {
                // Maker buys, gets base currency, commission in base currency
                $commissionWallet = $walletBaseCurrency;
            } else {
                // Maker sells, gets quote currency, commission in quote currency
                $commissionWallet = $walletQuoteCurrency;
            }
        } else {
            $commissionAmount = $takerCommissionAmount;
            // If this is the taker's transaction
            if ($spotOrder->side === SpotOrderSideEnum::BUY) {
                // Taker buys, gets base currency, commission in base currency
                $commissionWallet = $walletQuoteCurrency;
            } else {
                // Taker sells, gets quote currency, commission in quote currency
                $commissionWallet = $walletBaseCurrency;
            }
        }

        // **Create Transaction for Commission**
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setSpotTradeId($spotTrade->id)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::FEE)
                ->setAmount($commissionAmount * -1)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($commissionWallet->balance)
                ->setDescription(
                    sprintf('کارمزد معامله اسپات %s %s به ارزش %s',
                        $spotOrder->side === SpotOrderSideEnum::BUY ? 'خرید' : 'فروش',
                        $spotOrder->market->base_currency,
                        formatNumberTrimZeros((float)$commissionAmount),
                    ))
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
        $expectedTradeCost = Math::mul($tradeQuantity, $takerOrder->price); // Original order price

        // **Taker Updates**
        if ($takerOrder->side === SpotOrderSideEnum::BUY) {
            // Buyer (Taker) receives base currency, pays in quote currency
            // Commission is taken from base currency (what they receive)
            $takerReceiveQty = Math::sub($tradeQuantity, $takerCommission);
            $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $actualTradeCost);
            $this->walletRepository->increaseBalance($takerOrder->user_id, $baseCurrency, $takerReceiveQty);

            //Decrease Quote Currency
            $this->walletRepository->decreaseBalance($takerOrder->user_id, $quoteCurrency, $actualTradeCost);

            // **Refund remaining USDT if price was lower than expected**
            $refundAmount = Math::sub($expectedTradeCost, $actualTradeCost);
            if (Math::comp($refundAmount, 0) === 1) {
                $this->walletRepository->increaseBalance($takerOrder->user_id, $quoteCurrency, $refundAmount);
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $refundAmount);
            }
        } else {
            // Seller (Taker) receives quote currency, pays in base currency
            // Commission is taken from quote currency (what they receive)
            $takerReceiveQuote = Math::sub($actualTradeCost, $takerCommission);

            $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $baseCurrency, $tradeQuantity);


            $this->walletRepository->increaseBalance($takerOrder->user_id, $quoteCurrency, $takerReceiveQuote);

            //Decrease Quote Currency
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
        OrderBookUpdated::dispatch($marketId);
    }

    private function broadcastUserNotifications(int $userId): void
    {
        UserNotification::dispatch($userId, __('user_notifications.spot_order.order_completed', locale: 'fa'));
    }
}
