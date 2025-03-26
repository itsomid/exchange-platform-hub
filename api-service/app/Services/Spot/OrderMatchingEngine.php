<?php

namespace App\Services\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\OrderBookUpdated;
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
            //Maybe this order matched with another order
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
            ->lockForUpdate()
            ->get();

        foreach ($oppositeOrders as $oppositeOrder) {
            if ($order->getRemindedQuantity() <= 0) {
                break;
            }
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
            ->when($order->side === SpotOrderSideEnum::BUY, fn ($query) => $query->where('price', '<=', $order->price))
            ->when($order->side === SpotOrderSideEnum::SELL, fn ($query) => $query->where('price', '>=', $order->price))
            ->orderBy('price', $sortType)
            ->lockForUpdate()
            ->get();

        foreach ($oppositeOrders as $oppositeOrder) {
            if ($order->getRemindedQuantity() <= 0) {
                break;
            }
            $this->completeOrder($order, $oppositeOrder);
            $this->broadcastOrderBook($order->market_id);
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
            ]);

        $this->updateWallets($takerOrder, $makerOrder, $tradeQuantity, $makerOrder->price);

        $commission = $this->calcCommission($tradeQuantity);
        TradingCommission::query()
            ->create([
                'spot_trade_id' => $spotTrade->id,
                'maker_commission_amount' => $commission,
                'maker_commission_percentage' => 0.001,
                'taker_commission_amount' => $commission,
                'taker_commission_percentage' => 0.001,
            ]);

        if ($order->getRemindedQuantity() <= 0) {
            $order->update(['status' => SpotOrderStatusEnum::COMPLETED]);
        }

        if ($oppositeOrder->getRemindedQuantity() <= 0) {
            $oppositeOrder->update(['status' => SpotOrderStatusEnum::COMPLETED]);
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

    private function updateWallets(SpotOrder $takerOrder, SpotOrder $makerOrder, $tradeQuantity, $tradePrice): void
    {
        $baseCurrency = $takerOrder->market->base_currency; // Example: BTC
        $quoteCurrency = $takerOrder->market->quote_currency; // Example: USDT

        $actualTradeCost = Math::mul($tradeQuantity, $tradePrice);
        $expectedTradeCost = Math::mul($tradeQuantity, $takerOrder->price); // Original order price

        DB::transaction(function () use ($takerOrder, $makerOrder, $tradeQuantity, $actualTradeCost, $expectedTradeCost, $baseCurrency, $quoteCurrency) {
            // **Taker Updates**
            if ($takerOrder->side === SpotOrderSideEnum::BUY) {
                // Buyer (Taker) receives base currency, pays in quote currency
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $actualTradeCost);
                $this->walletRepository->increaseBalance($takerOrder->user_id, $baseCurrency, $tradeQuantity);

                // **Refund remaining USDT if price was lower than expected**
                $refundAmount = Math::sub($expectedTradeCost, $actualTradeCost);
                if (Math::comp($refundAmount, 0) === 1) {
                    $this->walletRepository->increaseBalance($takerOrder->user_id, $quoteCurrency, $refundAmount);
                    $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $quoteCurrency, $refundAmount);
                }
            } else {
                // Seller (Taker) receives quote currency, pays in base currency
                $this->walletRepository->decreaseLockedBalance($takerOrder->user_id, $baseCurrency, $tradeQuantity);
                $this->walletRepository->increaseBalance($takerOrder->user_id, $quoteCurrency, $actualTradeCost);
            }

            LockedBalanceDetail::query()
                ->where('spot_order_id', $takerOrder->id)
                ->delete();

            // **Maker Updates**
            if ($makerOrder->side === SpotOrderSideEnum::BUY) {
                // Buyer (Maker) receives base currency, pays in quote currency
                $this->walletRepository->increaseBalance($makerOrder->user_id, $baseCurrency, $tradeQuantity);
                $this->walletRepository->decreaseLockedBalance($makerOrder->user_id, $quoteCurrency, $actualTradeCost);
            } else {
                // Seller (Maker) receives quote currency, pays in base currency
                $this->walletRepository->increaseBalance($makerOrder->user_id, $quoteCurrency, $actualTradeCost);
                $this->walletRepository->decreaseLockedBalance($makerOrder->user_id, $baseCurrency, $tradeQuantity);
            }
            LockedBalanceDetail::query()
                ->where('spot_order_id', $makerOrder->id)
                ->delete();
        });
    }

    private function broadcastOrderBook(int $marketId): void
    {
        OrderBookUpdated::dispatch($marketId);
    }
}
