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
        try {
            return DB::transaction(function () use ($requestDTO) {
                //Find Market
                $market = Market::query()->find($requestDTO->getMarketId());
                $sellerWallet = Wallet::query()
                    ->where('user_id', $requestDTO->getSellerUserId())
                    ->where('currency_symbol', $market->base_currency)
                    ->lockForUpdate()
                    ->first();

                $buyerWallet = Wallet::query()
                    ->where('user_id', $requestDTO->getBuyerUserId())
                    ->where('currency_symbol', $market->base_currency)
                    ->lockForUpdate()
                    ->first();

                $sellerQuoteWallet = Wallet::query()
                    ->where('user_id', $requestDTO->getSellerUserId())
                    ->where('currency_symbol', $market->quote_currency)
                    ->lockForUpdate()
                    ->first();

                $buyerQuoteWallet = Wallet::query()
                    ->where('user_id', $requestDTO->getBuyerUserId())
                    ->where('currency_symbol', $market->quote_currency)
                    ->lockForUpdate()
                    ->first();


                $buyAmount = $requestDTO->getQuantity();
                $amountInQuoteCurrency = bcmul($market->activeExchangePrice->price, $requestDTO->getQuantity(), 8);
                $fee = bcmul($buyAmount, Setting::getSetting('otc_buy_fee'), 8);
                $receivedAmount = bcsub($buyAmount, $fee, 8);

                if ($buyerQuoteWallet->balance < $amountInQuoteCurrency) {
                    throw new InsufficientBalanceException("Buyer does not have enough ". $market->quote_currency);
                }

                if ($sellerWallet->balance < $receivedAmount) {
                    throw new InsufficientBalanceException("Seller does not have enough base currency. ". $market->base_currency);
                }

                $otc_order = OTCOrder::query()->create([
                    'user_id' => $requestDTO->getBuyerUserId(), // The actual user initiating the transaction
                    'market_id' => $market->id,
                    'quantity' => $buyAmount,
                    'price' => $market->activeExchangePrice->price,
                    'fee' => $fee,
                    'type' => 'buy',
                    'status' => OTCOrderStatusEnum::SUCCESS,
                ]);

                // Buyer transaction (Base currency)
                Transaction::query()->create([
                    'user_id' => $requestDTO->getBuyerUserId(),
                    'wallet_id' => $buyerWallet->id,
                    'otc_order_id' => $otc_order->id,
                    'balance' => $buyerWallet->balance,
                    'amount' => $receivedAmount,
                    'type' => TransactionTypeEnum::BUY,
                    'subtype' => TransactionSubTypeEnum::OTC,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => "خرید " . formatNumberTrimZeros($buyAmount)
                        . " {$market->base_currency} به قیمت "
                        . formatNumberTrimZeros($market->activeExchangePrice->price) . " تتر",
                ]);
                $buyerWallet->increment('balance', $receivedAmount);

                // Buyer transaction (Quote currency)
                Transaction::query()->create([
                    'user_id' => $requestDTO->getBuyerUserId(),
                    'wallet_id' => $buyerQuoteWallet->id,
                    'otc_order_id' => $otc_order->id,
                    'balance' => $buyerQuoteWallet->balance,
                    'amount' => -$amountInQuoteCurrency,
                    'type' => TransactionTypeEnum::SELL,
                    'subtype' => TransactionSubTypeEnum::OTC,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => "خرید " . formatNumberTrimZeros($buyAmount)
                        . " {$market->base_currency} به قیمت "
                        . formatNumberTrimZeros($market->activeExchangePrice->price) . " تتر",
                ]);
                $buyerQuoteWallet->decrement('balance', $amountInQuoteCurrency);


                // Seller transaction (Quote currency)
                Transaction::query()->create([
                    'user_id' => $requestDTO->getSellerUserId(),
                    'wallet_id' => $sellerQuoteWallet->id,
                    'otc_order_id' => $otc_order->id,
                    'balance' => $sellerQuoteWallet->balance,
                    'amount' => $amountInQuoteCurrency,
                    'type' => TransactionTypeEnum::BUY,
                    'subtype' => TransactionSubTypeEnum::OTC,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => "فروش " . formatNumberTrimZeros($buyAmount)
                        . " {$market->base_currency} به قیمت "
                        . formatNumberTrimZeros($market->activeExchangePrice->price) . " تتر",
                ]);
                $sellerQuoteWallet->increment('balance', $amountInQuoteCurrency);

                // Seller transaction (Base currency)
                Transaction::query()->create([
                    'user_id' => $requestDTO->getSellerUserId(),
                    'wallet_id' => $sellerWallet->id,
                    'otc_order_id' => $otc_order->id,
                    'balance' => $sellerWallet->balance,
                    'amount' => -$receivedAmount,
                    'type' => TransactionTypeEnum::SELL,
                    'subtype' => TransactionSubTypeEnum::OTC,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => "فروش " . formatNumberTrimZeros($buyAmount)
                        . " {$market->base_currency} به قیمت "
                        . formatNumberTrimZeros($market->activeExchangePrice->price) . " تتر",
                ]);

                $sellerWallet->decrement('balance', $receivedAmount);




                // 3. Commission Transaction
                Transaction::query()->create([
                    'user_id' => $requestDTO->getSellerUserId(),
                    'wallet_id' => $sellerWallet->id,
                    'otc_order_id' => $otc_order->id,
                    'balance' => $sellerWallet->balance,
                    'amount' => $fee,
                    'type' => TransactionTypeEnum::FEE,
                    'subtype' => TransactionSubTypeEnum::OTC,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => "کارمزد معامله  {$market->base_currency} به ارزش  " . formatNumberTrimZeros($buyAmount),
                ]);
                return true;
            });


        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }

    }
}
