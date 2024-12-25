<?php

namespace App\Services\OTC;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Market;
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
            DB::beginTransaction();
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

            // Calculate amounts
            $amountInQuoteCurrency = bcmul($market->activeExchangePrice->price, $requestDTO->getQuantity(), 8);
            $commission = bcmul($requestDTO->getQuantity(), Setting::getSetting('otc_buy_fee'), 8);
            $receivedAmount = bcsub($requestDTO->getQuantity(), $commission, 8);

            // 1. Transactions for the base currency
            // Seller transaction (Base currency)
            Transaction::query()->create([
                'user_id' => $requestDTO->getSellerUserId(),
                'wallet_id' => $sellerWallet->id,
                'balance' => $sellerWallet->balance,
                'amount' => $receivedAmount,
                'type' => TransactionTypeEnum::OTC_SELL,
                'subtype' => TransactionSubTypeEnum::OTC,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "OTC Sell: {$market->base_currency} to buyer ID {$requestDTO->getBuyerUserId()}",
            ]);
            $sellerWallet->decrement('balance', $receivedAmount);
            // Buyer transaction (Base currency)
            Transaction::query()->create([
                'user_id' => $requestDTO->getBuyerUserId(),
                'wallet_id' => $buyerWallet->id,
                'balance' => $buyerWallet->balance,
                'amount' => $receivedAmount,
                'type' => TransactionTypeEnum::OTC_BUY,
                'subtype' => TransactionSubTypeEnum::OTC,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "OTC Buy: {$market->base_currency} from seller ID {$requestDTO->getSellerUserId()}",

            ]);
            $buyerWallet->decrement('balance', $receivedAmount);

            // 2. Transactions for the quote currency
            // Seller transaction (Quote currency)
            Transaction::query()->create([
                'user_id' => $requestDTO->getSellerUserId(),
                'wallet_id' => $sellerQuoteWallet->id,
                'balance' => $sellerQuoteWallet->balance,
                'amount' => $amountInQuoteCurrency,
                'type' => TransactionTypeEnum::OTC_BUY,
                'subtype' => TransactionSubTypeEnum::OTC,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "OTC Sell: Received {$market->quote_currency} from buyer ID {$requestDTO->getBuyerUserId()}",
            ]);
            $sellerQuoteWallet->increment('balance', $amountInQuoteCurrency);
            // Buyer transaction (Quote currency)
            Transaction::query()->create([
                'user_id' => $requestDTO->getBuyerUserId(),
                'wallet_id' => $buyerQuoteWallet->id,
                'balance' => $buyerQuoteWallet->balance,
                'amount' => $amountInQuoteCurrency,
                'type' => TransactionTypeEnum::OTC_SELL,
                'subtype' => TransactionSubTypeEnum::OTC,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "OTC Buy: Spent {$market->quote_currency} to seller ID {$requestDTO->getSellerUserId()}",
            ]);
            $buyerQuoteWallet->decrement('balance', $amountInQuoteCurrency);

            // 3. Commission Transaction
            Transaction::query()->create([
                'user_id' => $requestDTO->getSellerUserId(),
                'wallet_id' => $sellerWallet->id,
                'balance' => $sellerWallet->balance,
                'amount' => $commission,
                'type' => TransactionTypeEnum::FEE,
                'subtype' => TransactionSubTypeEnum::OTC,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "OTC Fee: Commission deducted for {$market->base_currency} transaction",
            ]);

            DB::commit();

            return true;
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return false;
        }

    }
}
