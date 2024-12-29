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
use App\Services\OTC\DTO\BuyRequestDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class OTCService
{

    public function buy(BuyRequestDTO $requestDTO): bool
    {
        return $this->processTransaction($requestDTO, 'buy');
    }

    public function sell(BuyRequestDTO $requestDTO): bool
    {
        return $this->processTransaction($requestDTO, 'sell');
    }

    private function processTransaction(BuyRequestDTO $requestDTO, string $type): bool
    {
        try {
            return DB::transaction(function () use ($requestDTO, $type) {
                // Find Market
                $market = Market::query()->find($requestDTO->getMarketId());

                $sellerWallet = $this->getWallet($requestDTO->getSellerUserId(), $market->base_currency);
                $buyerWallet = $this->getWallet($requestDTO->getBuyerUserId(), $market->base_currency);
                $sellerQuoteWallet = $this->getWallet($requestDTO->getSellerUserId(), $market->quote_currency);
                $buyerQuoteWallet = $this->getWallet($requestDTO->getBuyerUserId(), $market->quote_currency);

                $amount = $requestDTO->getQuantity();
                $amountInQuoteCurrency = bcmul($market->activeExchangePrice->price, $amount, 8);
                $fee = bcmul($amount, Setting::getSetting($type === 'buy' ? 'otc_buy_fee' : 'otc_sell_fee'), 8);
                $receivedAmount = $type === 'buy' ? bcsub($amount, $fee, 8) : bcsub($amountInQuoteCurrency, $fee, 8);

                $this->validateBalances($type, $buyerQuoteWallet, $sellerWallet, $amountInQuoteCurrency, $receivedAmount, $market);

                $otc_order = OTCOrder::query()->create([
                    'user_id' => $type === 'buy' ? $requestDTO->getBuyerUserId() : $requestDTO->getSellerUserId(),
                    'market_id' => $market->id,
                    'quantity' => $amount,
                    'price' => $market->activeExchangePrice->price,
                    'fee' => $fee,
                    'type' => $type,
                    'status' => OTCOrderStatusEnum::SUCCESS,
                ]);

                if ($type === 'buy') {
                    $this->processBuyTransactions($requestDTO, $buyerWallet, $sellerWallet, $buyerQuoteWallet, $sellerQuoteWallet, $amount, $amountInQuoteCurrency, $receivedAmount, $fee, $otc_order, $market);
                } else {
                    $this->processSellTransactions($requestDTO, $sellerWallet, $buyerWallet, $sellerQuoteWallet, $buyerQuoteWallet, $amount, $amountInQuoteCurrency, $receivedAmount, $fee, $otc_order, $market);
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

    private function validateBalances(string $type, $buyerQuoteWallet, $sellerWallet, $amountInQuoteCurrency, $receivedAmount, $market)
    {
        if ($type === 'buy') {
            if ($buyerQuoteWallet->balance < $amountInQuoteCurrency) {
                throw new InsufficientBalanceException("Buyer does not have enough {$market->quote_currency}");
            }

            if ($sellerWallet->balance < $receivedAmount) {
                throw new InsufficientBalanceException("Seller does not have enough base currency. {$market->base_currency}");
            }
        } else {
            if ($sellerWallet->balance < $receivedAmount) {
                throw new InsufficientBalanceException("Seller does not have enough {$market->base_currency}");
            }

            if ($buyerQuoteWallet->balance < $amountInQuoteCurrency) {
                throw new InsufficientBalanceException("Buyer does not have enough {$market->quote_currency}");
            }
        }
    }

    private function processBuyTransactions($requestDTO, $buyerWallet, $sellerWallet, $buyerQuoteWallet, $sellerQuoteWallet, $amount, $amountInQuoteCurrency, $receivedAmount, $fee, $otc_order, $market)
    {
        $this->createTransaction($requestDTO->getBuyerUserId(), $buyerWallet, $otc_order, $receivedAmount, TransactionTypeEnum::BUY, $market, $amount, 'خرید');
        $buyerWallet->increment('balance', $receivedAmount);

        $this->createTransaction($requestDTO->getBuyerUserId(), $buyerQuoteWallet, $otc_order, -$amountInQuoteCurrency, TransactionTypeEnum::SELL, $market, $amount, 'خرید');
        $buyerQuoteWallet->decrement('balance', $amountInQuoteCurrency);

        $this->createTransaction($requestDTO->getSellerUserId(), $sellerQuoteWallet, $otc_order, $amountInQuoteCurrency, TransactionTypeEnum::BUY, $market, $amount, 'فروش');
        $sellerQuoteWallet->increment('balance', $amountInQuoteCurrency);

        $this->createTransaction($requestDTO->getSellerUserId(), $sellerWallet, $otc_order, -$receivedAmount, TransactionTypeEnum::SELL, $market, $amount, 'فروش');
        $sellerWallet->decrement('balance', $receivedAmount);

        $this->createCommissionTransaction($requestDTO->getSellerUserId(), $sellerWallet, $otc_order, $fee, $market, $amount, 'کارمزد معامله');
    }

    private function processSellTransactions($requestDTO, $sellerWallet, $buyerWallet, $sellerQuoteWallet, $buyerQuoteWallet, $amount, $amountInQuoteCurrency, $receivedAmount, $fee, $otc_order, $market)
    {
        $this->createTransaction($requestDTO->getSellerUserId(), $sellerWallet, $otc_order, -$amount, TransactionTypeEnum::SELL, $market, $amount, 'فروش');
        $sellerWallet->decrement('balance', $amount);

        $this->createTransaction($requestDTO->getSellerUserId(), $sellerQuoteWallet, $otc_order, $receivedAmount, TransactionTypeEnum::BUY, $market, $amount, 'فروش');
        $sellerQuoteWallet->increment('balance', $receivedAmount);

        $this->createTransaction($requestDTO->getBuyerUserId(), $buyerQuoteWallet, $otc_order, -$amountInQuoteCurrency, TransactionTypeEnum::SELL, $market, $amount, 'خرید');
        $buyerQuoteWallet->decrement('balance', $amountInQuoteCurrency);

        $this->createTransaction($requestDTO->getBuyerUserId(), $buyerWallet, $otc_order, $amount, TransactionTypeEnum::BUY, $market, $amount, 'خرید');
        $buyerWallet->increment('balance', $amount);

        $this->createCommissionTransaction($requestDTO->getSellerUserId(), $sellerQuoteWallet, $otc_order, -$fee, $market, $amount, 'کارمزد معامله');
    }

    private function createTransaction($userId, $wallet, $otc_order, $amount, $type, $market, $quantity, $description)
    {
        Transaction::query()->create([
            'user_id' => $userId,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otc_order->id,
            'balance' => $wallet->balance,
            'amount' => $amount,
            'type' => $type,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => "$description " . formatNumberTrimZeros($quantity)
                . " {$market->base_currency} به قیمت "
                . formatNumberTrimZeros($market->activeExchangePrice->price) . " تتر",
        ]);
    }

    private function createCommissionTransaction($userId, $wallet, $otc_order, $fee, $market, $quantity, $description)
    {
        Transaction::query()->create([
            'user_id' => $userId,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otc_order->id,
            'balance' => $wallet->balance,
            'amount' => $fee,
            'type' => TransactionTypeEnum::FEE,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => "$description  {$market->quote_currency} به ارزش  " . formatNumberTrimZeros($quantity),
        ]);
    }

}
