<?php

namespace App\Services\OTC;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\V1\OTC\InsufficientBalanceException;
use App\Models\Market;
use App\Models\Setting;
use App\Models\Transaction;
use App\Repositories\DTO\OTCOrder\CreateOTCOrderRequestDTO;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\OTC\DTO\MarketResponseDTO;
use App\Services\OTC\DTO\OTCBuyRequestDTO;
use App\Services\OTC\DTO\OTCBuyResponseDTO;
use App\Services\OTC\DTO\OTCSellRequestDTO;
use App\Services\ReferralCode\ReferralCommissionService;
use Throwable;

class OTCService
{
    public function __construct(
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly OTCOrderRepositoryInterface $otcOrderRepository,
        private readonly ReferralCommissionService $referralCommissionService
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

    /**
     * @throws Throwable
     * @throws InsufficientBalanceException
     */
    public function buy(OTCBuyRequestDTO $requestDTO): OTCBuyResponseDTO
    {
        try {
            //Find Market
            $market = Market::query()->find($requestDTO->getMarketId());
            $sellerWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->base_currency,
                    $requestDTO->getSellerUserId()
                );
            $buyerWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->base_currency,
                    $requestDTO->getBuyerUserId()
                );
            $sellerQuoteWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->quote_currency,
                    $requestDTO->getSellerUserId()
                );
            $buyerQuoteWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->quote_currency,
                    $requestDTO->getBuyerUserId()
                );

            $buyAmount = $requestDTO->getQuantity();
            $amountInQuoteCurrency = bcmul($market->exchangePrice->price, $requestDTO->getQuantity(), 8);
            $fee = bcmul($buyAmount, Setting::getSetting('otc_buy_fee'), 8);
            $receivedAmount = bcsub($buyAmount, $fee, 8);

            if (bccomp($buyerQuoteWallet->balance, $amountInQuoteCurrency, 8) === -1) {
                throw new InsufficientBalanceException(__('otc.buyer_insufficient_balance', ['currency' => $market->quote_currency]));
            }

            if (bccomp($sellerWallet->balance, $receivedAmount, 8) === -1) {
                throw new InsufficientBalanceException(__('otc.seller_insufficient_balance', ['currency' => $market->base_currency]));
            }

            $otc_order = $this->otcOrderRepository->create(resolve(CreateOTCOrderRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId()) // The actual user initiating the transaction
                ->setMarketId($market->id)
                ->setQuantity($buyAmount)
                ->setPrice($market->exchangePrice->price)
                ->setFee($fee)
                ->setType(OTCOrderTypeEnum::BUY)
                ->setStatus(OTCOrderStatusEnum::SUCCESS)
            );
            if ($otc_order->user->introducer_code) {
                $fee = $this->referralCommissionService->processReferralCommission($otc_order, $fee);
            }

            // Buyer transaction (Base currency)
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId())
                ->setWalletId($buyerWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($buyerWallet->balance)
                ->setAmount($receivedAmount)
                ->setType(TransactionTypeEnum::BUY)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('خرید %s %s به قیمت %s %s',
                    formatNumberTrimZeros((float) $buyAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $market->exchangePrice->price),
                    $market->quote_currency))
            );

            $buyerWallet->increment('balance', (float) $receivedAmount);

            // Buyer transaction (Quote currency)
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId())
                ->setWalletId($buyerQuoteWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($buyerQuoteWallet->balance)
                ->setAmount((string) -$amountInQuoteCurrency)
                ->setType(TransactionTypeEnum::SELL)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('خرید %s %s به قیمت %s %s',
                    formatNumberTrimZeros((float) $buyAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $market->exchangePrice->price),
                    $market->quote_currency)
                )
            );
            $buyerQuoteWallet->decrement('balance', (float) $amountInQuoteCurrency);

            // Seller transaction (Quote currency)
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerQuoteWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($sellerQuoteWallet->balance)
                ->setAmount($amountInQuoteCurrency)
                ->setType(TransactionTypeEnum::BUY)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('فروش %s %s به قیمت %s %s',
                    formatNumberTrimZeros((float) $buyAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $market->exchangePrice->price),
                    $market->quote_currency)
                )
            );
            $sellerQuoteWallet->increment('balance', (float) $amountInQuoteCurrency);

            // Seller transaction (Base currency)
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($sellerWallet->balance)
                ->setAmount((string) -$receivedAmount)
                ->setType(TransactionTypeEnum::SELL)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf('فروش %s %s به قیمت %s %s',
                        formatNumberTrimZeros((float) $buyAmount),
                        $market->base_currency,
                        formatNumberTrimZeros((float) $market->exchangePrice->price),
                        $market->quote_currency)
                )
            );

            $sellerWallet->decrement('balance', (float) $receivedAmount);

            // 3. Commission Transaction
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($sellerWallet->balance)
                ->setAmount($fee)
                ->setType(TransactionTypeEnum::FEE)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('کارمزد معامله %s %s به ارزش %s %s',
                    formatNumberTrimZeros((float) $buyAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $fee),
                    $market->base_currency)
                ));

        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }

        return resolve(OTCBuyResponseDTO::class)
            ->setOtcOrderModel($otc_order);
    }

    /**
     * @throws Throwable
     * @throws InsufficientBalanceException
     */
    public function sell(OTCSellRequestDTO $requestDTO): OTCBuyResponseDTO
    {
        try {
            // Find Market
            $market = Market::query()->find($requestDTO->getMarketId());
            $buyerWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->base_currency,
                    $requestDTO->getBuyerUserId()
                );
            $sellerWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->base_currency,
                    $requestDTO->getSellerUserId()
                );
            $buyerQuoteWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->quote_currency,
                    $requestDTO->getBuyerUserId()
                );
            $sellerQuoteWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->quote_currency,
                    $requestDTO->getSellerUserId()
                );

            $sellAmount = $requestDTO->getQuantity();
            $amountInQuoteCurrency = bcmul($market->exchangePrice->price, $sellAmount, 8);
            $fee = bcmul($sellAmount, Setting::getSetting('otc_sell_fee'), 8);
            $receivedAmount = bcsub($amountInQuoteCurrency, $fee, 8);

            if (bccomp($sellerWallet->balance, $sellAmount, 8) === -1) {
                throw new InsufficientBalanceException(__('otc.seller_insufficient_balance', ['currency' => $market->base_currency]));
            }

            if (bccomp($buyerQuoteWallet->balance, $amountInQuoteCurrency, 8) === -1) {
                throw new InsufficientBalanceException(__('otc.buyer_insufficient_balance', ['currency' => $market->quote_currency]));
            }

            $otc_order = $this->otcOrderRepository->create(resolve(CreateOTCOrderRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setMarketId($market->id)
                ->setQuantity($sellAmount)
                ->setPrice($market->exchangePrice->price)
                ->setFee($fee)
                ->setType(OTCOrderTypeEnum::SELL)
                ->setStatus(OTCOrderStatusEnum::SUCCESS)
            );

            if ($otc_order->user->introducer_code) {
                $fee = $this->referralCommissionService->processReferralCommission($otc_order, $fee);
            }

            // Seller transaction (Base currency)
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($sellerWallet->balance)
                ->setAmount((string) -$sellAmount)
                ->setType(TransactionTypeEnum::SELL)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('فروش %s %s به قیمت %s %s',
                    formatNumberTrimZeros((float) $sellAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $market->exchangePrice->price),
                    $market->quote_currency)
                )
            );
            $sellerWallet->decrement('balance', (float) $sellAmount);

            // Seller transaction (Quote currency)
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerQuoteWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($sellerQuoteWallet->balance)
                ->setAmount($receivedAmount)
                ->setType(TransactionTypeEnum::BUY)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('فروش %s %s به قیمت %s %s',
                    formatNumberTrimZeros((float) $sellAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $market->exchangePrice->price),
                    $market->quote_currency)
                )
            );
            $sellerQuoteWallet->increment('balance', (float) $receivedAmount);

            // Buyer transaction (Base currency)
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId())
                ->setWalletId($buyerWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($buyerWallet->balance)
                ->setAmount($sellAmount)
                ->setType(TransactionTypeEnum::BUY)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('خرید %s %s به قیمت %s %s',
                    formatNumberTrimZeros((float) $sellAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $market->exchangePrice->price),
                    $market->quote_currency)
                )
            );
            $buyerWallet->increment('balance', (float) $sellAmount);

            // Buyer transaction (Quote currency)
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId())
                ->setWalletId($buyerQuoteWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($buyerQuoteWallet->balance)
                ->setAmount((string) -$amountInQuoteCurrency)
                ->setType(TransactionTypeEnum::SELL)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('خرید %s %s به قیمت %s %s',
                    formatNumberTrimZeros((float) $sellAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $market->exchangePrice->price),
                    $market->quote_currency)
                )
            );
            $buyerQuoteWallet->decrement('balance', (float) $amountInQuoteCurrency);

            // Commission Transaction
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.bitexroom_user_id'))
                ->setWalletId($sellerQuoteWallet->id)
                ->setOtcOrderId($otc_order->id)
                ->setBalance($sellerQuoteWallet->balance)
                ->setAmount($fee)
                ->setType(TransactionTypeEnum::FEE)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('کارمزد معامله %s %s به ارزش %s %s',
                    formatNumberTrimZeros((float) $sellAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float) $fee),
                    $market->quote_currency)
                )
            );

        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }

        return resolve(OTCBuyResponseDTO::class)
            ->setOtcOrderModel($otc_order);
    }
}
