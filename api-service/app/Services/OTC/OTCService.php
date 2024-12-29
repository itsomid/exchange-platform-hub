<?php

namespace App\Services\OTC;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\V1\OTC\InsufficientBalanceException;
use App\Models\Market;
use App\Models\OTCOrder;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\OTC\DTO\MarketResponseDTO;
use App\Services\OTC\DTO\OTCBuyRequestDTO;
use Throwable;

class OTCService
{
    public function __construct(
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly WalletRepositoryInterface $walletRepository
    ) {}

    public function markets(): array
    {
        $markets = $this->marketRepository->getOTCMarkets();

        return $markets->map(fn (Market $market) => resolve(MarketResponseDTO::class)
            ->setMarketId($market->id)
            ->setBaseCurrency($market->base_currency)
            ->setQuoteCurrency($market->quote_currency)
            ->setIsActive($market->is_active)
            ->setSellPrice(bcmul($market->exchangePrice->price, (string) ($market->exchangePrice->exchange_profit_sell + 1), 8))
            ->setBuyPrice(bcmul($market->exchangePrice->price, (string) ($market->exchangePrice->exchange_profit_buy + 1), 8))
            ->setMinTradeAmount($market->min_trade_amount)
            ->setMaxTradeAmount($market->max_trade_amount)
        )->toArray();
    }

    public function bitexroomAvailableBalance(int $marketId): string
    {
        $market = $this->marketRepository->getMarketById($marketId);

        $wallet = $this->walletRepository->getOneByCurrency(
            $market->base_currency,
            config('bitexroom.bitexroom_user_id')
        );

        return $wallet->available;
    }

    public function buy(OTCBuyRequestDTO $requestDTO): void
    {
        try {

            //Find Market
            $market = Market::query()->find($requestDTO->getMarketId());
            $sellerWallet = Wallet::query()
                ->where('user_id', $requestDTO->getSellerUserId())
                ->where('currency_symbol', $market->base_currency)
                ->lockForUpdate()
                ->first();

            $buyerWallet = Wallet::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    [
                        'user_id' => $requestDTO->getBuyerUserId(),
                        'currency_symbol' => $market->base_currency,
                    ]
                );

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
            $amountInQuoteCurrency = bcmul($market->exchangePrice->price, $requestDTO->getQuantity(), 8);
            $fee = bcmul($buyAmount, Setting::getSetting('otc_buy_fee'), 8);
            $receivedAmount = bcsub($buyAmount, $fee, 8);

            if ($buyerQuoteWallet->balance < $amountInQuoteCurrency) {
                throw new InsufficientBalanceException('Buyer does not have enough '.$market->quote_currency);
            }

            if ($sellerWallet->balance < $receivedAmount) {
                throw new InsufficientBalanceException('Seller does not have enough base currency. '.$market->base_currency);
            }

            $otc_order = OTCOrder::query()->create([
                'user_id' => $requestDTO->getBuyerUserId(), // The actual user initiating the transaction
                'market_id' => $market->id,
                'quantity' => $buyAmount,
                'price' => $market->exchangePrice->price,
                'fee' => $fee,
                'type' => OTCOrderTypeEnum::BUY,
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
                'description' => 'خرید '.number_format($buyAmount)
                    ." {$market->base_currency} به قیمت "
                    .number_format($market->exchangePrice->price).' تتر',
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
                'description' => 'خرید '.number_format($buyAmount)
                    ." {$market->base_currency} به قیمت "
                    .number_format($market->exchangePrice->price).' تتر',
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
                'description' => 'فروش '.number_format($buyAmount)
                    ." {$market->base_currency} به قیمت "
                    .number_format($market->exchangePrice->price).' تتر',
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
                'description' => 'فروش '.number_format($buyAmount)
                    ." {$market->base_currency} به قیمت "
                    .number_format($market->exchangePrice->price).' تتر',
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
                'description' => "کارمزد معامله  {$market->base_currency} به ارزش  ".number_format($buyAmount),
            ]);

        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }

    }
}
