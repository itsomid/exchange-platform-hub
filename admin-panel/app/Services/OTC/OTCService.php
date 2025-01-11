<?php

namespace App\Services\OTC;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Market;
use App\Models\OTCOrder;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\OTC\DTO\OTCRequestDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class OTCService
{
    protected $exchangeUserId;

    public function __construct()
    {
        $this->exchangeUserId = config('exchange.exchange_user_id');
    }

    public function buy(OTCRequestDTO $requestDTO): bool
    {

        return $this->processTransaction($requestDTO, 'buy');
    }

    public function sell(OTCRequestDTO $requestDTO): bool
    {
        return $this->processTransaction($requestDTO, 'sell');
    }

    private function processTransaction(OTCRequestDTO $requestDTO, string $type): bool
    {
        try {
            return DB::transaction(function () use ($requestDTO, $type) {
                // Find Market
                $market = Market::query()->find($requestDTO->getMarketId());

                $sellerWallet = $this->getWallet($requestDTO->getSellerUserId(), $market->base_currency);
                $buyerWallet = $this->getWallet($requestDTO->getBuyerUserId(), $market->base_currency);
                $sellerQuoteWallet = $this->getWallet($requestDTO->getSellerUserId(), $market->quote_currency);
                $buyerQuoteWallet = $this->getWallet($requestDTO->getBuyerUserId(), $market->quote_currency);

                $otcBuyFee = Setting::getSetting('otc_buy_fee') / 100;
                $otcSellFee = Setting::getSetting('otc_sell_fee') / 100;
                $quantity = $requestDTO->getQuantity();
                if ($type === 'buy') {
                    $orderUser = $requestDTO->getBuyerUserId();
                    $orderPrice = $market->activeExchangePrice->exchange_sell_price;
                    $amountInQuoteCurrency = bcmul($orderPrice, $quantity, 8);
                    $fee = bcmul($quantity, $otcBuyFee, 8);
                    $receivedAmount = bcsub($quantity, $fee, 8) ;
                } else {
                    $orderUser = $requestDTO->getSellerUserId();
                    $orderPrice = $market->activeExchangePrice->exchange_buy_price;
                    $amountInQuoteCurrency = bcmul($orderPrice, $quantity, 8);
                    $fee = bcmul($amountInQuoteCurrency, $otcSellFee, 8);
                    $receivedAmount = bcsub($amountInQuoteCurrency, $fee, 8);
                }


                $this->validateBalances($type, $buyerQuoteWallet, $sellerWallet,$quantity, $amountInQuoteCurrency, $receivedAmount, $market);

                $otc_order = OTCOrder::query()->create([
                    'user_id' => $orderUser,
                    'market_id' => $market->id,
                    'quantity' => $quantity,
                    'price' => $orderPrice,
                    'fee' => $fee,
                    'type' => $type,
                    'status' => OTCOrderStatusEnum::SUCCESS,
                ]);

                if ($type === 'buy') {

                    $this->processBuyTransactions(
                        $requestDTO->getBuyerUserId(),
                        $buyerWallet,
                        $sellerWallet,
                        $buyerQuoteWallet,
                        $sellerQuoteWallet,
                        $quantity,
                        $amountInQuoteCurrency,
                        $receivedAmount,
                        $fee,
                        $otc_order
                    );
                } else {
                    $this->processSellTransactions(
                        $requestDTO->getSellerUserId(),
                        $sellerWallet,
                        $buyerWallet,
                        $sellerQuoteWallet,
                        $buyerQuoteWallet,
                        $quantity,
                        $amountInQuoteCurrency,
                        $receivedAmount,
                        $fee,
                        $otc_order
                    );
                }

                return true;
            });
        } catch (Throwable $exception) {
            report($exception);
            throw $exception;
        }
    }

    private function getWallet(int $userId, string $currencySymbol)
    {
        return Wallet::query()
            ->where('user_id', $userId)
            ->where('currency_symbol', $currencySymbol)
            ->lockForUpdate()
            ->first();
    }

    private function validateBalances(string $type, $buyerQuoteWallet, $sellerWallet,$quantity, $amountInQuoteCurrency, $receivedAmount, $market)
    {
        if ($type === 'buy') {
            if ($buyerQuoteWallet->access_balance < $amountInQuoteCurrency) {
                throw new InsufficientBalanceException("Buyer does not have enough {$market->quote_currency}");
            }

            if ($sellerWallet->access_balance < $receivedAmount) {
                throw new InsufficientBalanceException("Seller does not have enough base currency. {$market->base_currency}");
            }
        } else {
            if ($sellerWallet->access_balance < $quantity) {
                throw new InsufficientBalanceException("Seller does not have enough {$market->base_currency}");
            }

            if ($buyerQuoteWallet->access_balance < $amountInQuoteCurrency) {
                throw new InsufficientBalanceException("Buyer does not have enough {$market->quote_currency}");
            }
        }
    }

    private function processBuyTransactions($buyerUserId, $buyerWallet, $sellerWallet, $buyerQuoteWallet, $sellerQuoteWallet, $quantity, $amountInQuoteCurrency, $receivedAmount, $fee, $otc_order)
    {
        $buyerWallet->increment('balance', $receivedAmount);
        $this->createTransaction($buyerUserId, $buyerWallet, $otc_order, $receivedAmount, TransactionTypeEnum::BUY, $quantity, 'خرید');

        $buyerQuoteWallet->decrement('balance', $amountInQuoteCurrency);
        $this->createTransaction($buyerUserId, $buyerQuoteWallet, $otc_order, -$amountInQuoteCurrency, TransactionTypeEnum::SELL, $quantity, 'خرید');

        $sellerQuoteWallet->increment('balance', $amountInQuoteCurrency);
        $this->createTransaction($this->exchangeUserId, $sellerQuoteWallet, $otc_order, $amountInQuoteCurrency, TransactionTypeEnum::BUY, $quantity, 'فروش');

        $sellerWallet->decrement('balance', $receivedAmount);
        $this->createTransaction($this->exchangeUserId, $sellerWallet, $otc_order, -$receivedAmount, TransactionTypeEnum::SELL, $quantity, 'فروش');

        $this->createCommissionTransaction($sellerWallet, $otc_order, $fee, $quantity);
    }

    private function processSellTransactions($sellerUserId, $sellerWallet, $buyerWallet, $sellerQuoteWallet, $buyerQuoteWallet, $quantity, $amountInQuoteCurrency, $receivedAmount, $fee, $otc_order)
    {
        $sellerWallet->decrement('balance', $quantity);
        $this->createTransaction($sellerUserId, $sellerWallet, $otc_order, -$quantity, TransactionTypeEnum::SELL, $quantity, 'فروش');

        $sellerQuoteWallet->increment('balance', $receivedAmount);
        $this->createTransaction($sellerUserId, $sellerQuoteWallet, $otc_order, $receivedAmount, TransactionTypeEnum::BUY, $quantity, 'فروش');

        $buyerQuoteWallet->decrement('balance', $receivedAmount);
        $this->createTransaction($this->exchangeUserId, $buyerQuoteWallet, $otc_order, -$receivedAmount, TransactionTypeEnum::SELL, $quantity, 'خرید');

        $buyerWallet->increment('balance', $quantity);
        $this->createTransaction($this->exchangeUserId, $buyerWallet, $otc_order, $quantity, TransactionTypeEnum::BUY, $quantity, 'خرید');

        if ($fee > 0) {
            $this->createCommissionTransaction($buyerQuoteWallet, $otc_order, $fee, $quantity);
        }
    }

    private function createTransaction($userId, $wallet, $otc_order, $receivedAmount, $type, $quantity, $description)
    {
        Transaction::query()->create([
            'user_id' => $userId,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otc_order->id,
            'balance' => $wallet->balance,
            'amount' => $receivedAmount,
            'type' => $type,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => "$description " . formatNumberTrimZeros($quantity)
            . " {$otc_order->market->base_currency} به قیمت "
            . formatNumberTrimZeros($otc_order->price) . " تتر",
        ]);
    }

    private function createCommissionTransaction($wallet, $otc_order, $fee, $quantity)
    {
        Transaction::query()->create([
            'user_id' => $this->exchangeUserId,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otc_order->id,
            'balance' => $wallet->balance,
            'amount' => $fee,
            'type' => TransactionTypeEnum::FEE,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => "کارمزد معامله  {$otc_order->market->base_currency} به ارزش  " . formatNumberTrimZeros($quantity),
        ]);

    }

}
