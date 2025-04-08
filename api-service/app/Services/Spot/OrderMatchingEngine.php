<?php

namespace App\Services\Spot;

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
        private WalletRepositoryInterface $walletRepository,
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
                logger()->error('Order matching failed: '.$e->getMessage(), ['exception' => $e]);
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

        $makerCommission = $this->calcCommission($tradeQuantity);
        $takerCommission = $this->calcCommission($tradeQuantity);
        TradingCommission::query()
            ->create([
                'spot_trade_id' => $spotTrade->id,
                'maker_commission_amount' => $makerCommission,
                'maker_commission_percentage' => 0.001,
                'taker_commission_amount' => $takeCommission,
                'taker_commission_percentage' => 0.001,
            ]);

        $this->updateWallets($takerOrder, $makerOrder, $tradeQuantity, $makerOrder->price, $makerCommission, $takerCommission);

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

    private function addTransactions(SpotOrder $spotOrder, SpotTrade $spotTrade)
    {
        $wallet = $this->walletRepository->getOneByCurrency($spotOrder->user_id, $spotOrder->market->base_currency);

        $bitexroomWallet = $this->walletRepository->getBitexroomWallet($spotOrder->market->base_currency);

        // **Create Transaction for Trader**
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::BUY)
                ->setAmount($spotTrade->quantity)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($wallet->balance)
                ->setDescription('test')
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($wallet->id)
        );
        // **Create Transaction for Commission**
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($spotOrder->user_id)
                ->setType(TransactionTypeEnum::FEE)
                ->setAmount($spotTrade->commission->quantity)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($wallet->balance)
                ->setDescription('test')
                ->setSubtype(TransactionSubTypeEnum::SPOT)
                ->setWalletId($wallet->id)
        );

    }

    public function calcCommission($tradeAmount): string
    {
        return Math::mul($tradeAmount, 0.001); // 0.1% commission
    }

    private function updateWallets(SpotOrder $takerOrder, SpotOrder $makerOrder, $tradeQuantity, $tradePrice, $makerCommission, $takerCommission): void
    {
        $baseCurrency = $takerOrder->market->base_currency; // Example: BTC
        $quoteCurrency = $takerOrder->market->quote_currency; // Example: USDT

        $actualTradeCost = Math::mul($tradeQuantity, $tradePrice);
        $expectedTradeCost = Math::mul($tradeQuantity, $takerOrder->price); // Original order price

        DB::transaction(function () use ($takerOrder, $makerOrder, $tradeQuantity, $actualTradeCost, $expectedTradeCost, $baseCurrency, $quoteCurrency, $makerCommission, $takerCommission) {
            // **Taker Updates**
            if ($takerOrder->side === SpotOrderSideEnum::BUY) {
                // Buyer (Taker) receives base currency, pays in quote currency
                $takerReceiveQty = Math::sub($tradeQuantity, $takerCommission);
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $actualTradeCost);
                $this->walletRepository->increaseBalance($takerOrder->user_id, $baseCurrency, $takerReceiveQty);

                // **Refund remaining USDT if price was lower than expected**
                $refundAmount = Math::sub($expectedTradeCost, $actualTradeCost);
                if (Math::comp($refundAmount, 0) === 1) {
                    $this->walletRepository->increaseBalance($takerOrder->user_id, $quoteCurrency, $refundAmount);
                    $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $refundAmount);
                }
            } else {
                $takerReceiveQuote = Math::sub($actualTradeCost, $takerCommission);
                // Seller (Taker) receives quote currency, pays in base currency
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $baseCurrency, $tradeQuantity);
                $this->walletRepository->increaseBalance($takerOrder->user_id, $quoteCurrency, $takerReceiveQuote);
            }

            // **Maker Updates**
            if ($makerOrder->side === SpotOrderSideEnum::BUY) {
                // Buyer (Maker) receives base currency, pays in quote currency
                $makerReceiveQty = Math::sub($tradeQuantity, $makerCommission);
                $this->walletRepository->increaseBalance($makerOrder->user_id, $baseCurrency, $makerReceiveQty);
                $this->walletRepository->decreaseLockedBalance($makerOrder->user_id, $quoteCurrency, $actualTradeCost);
            } else {
                $makerReceiveQuote = Math::sub($actualTradeCost, $makerCommission);
                // Seller (Maker) receives quote currency, pays in base currency
                $this->walletRepository->increaseBalance($makerOrder->user_id, $quoteCurrency, $makerReceiveQuote);
                $this->walletRepository->decreaseLockedBalance($makerOrder->user_id, $baseCurrency, $tradeQuantity);
            }
        });
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
