<?php

namespace App\Services\OTC;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Enums\SpotStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\V1\OTC\BuyTradeWasFiledException;
use App\Exceptions\V1\InsufficientBalanceException;
use App\Helpers\Math;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Setting;
use App\Models\Transaction;
use App\Notifications\OTCBuyCreated;
use App\Notifications\OTCSellCreated;
use App\Repositories\DTO\OTCOrder\CreateOTCOrderRequestDTO;
use App\Repositories\DTO\OTCRefExchangeWithdrawal\CreateOTCRefExchangeWithdrawalRequestDTO;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\ExchangeRepositoryInterface;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;
use App\Repositories\Interfaces\OTCRefExchangeWithdrawalInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Exchanges\DTO\ExchangeBuyRequestDTO;
use App\Services\Exchanges\ExchangeService;
use App\Services\OTC\DTO\CompletedOrderRequestDTO;
use App\Services\OTC\DTO\MarketResponseDTO;
use App\Services\OTC\DTO\OTCBuyRequestDTO;
use App\Services\OTC\DTO\OTCBuyResponseDTO;
use App\Services\OTC\DTO\OTCSellRequestDTO;
use App\Services\ReferralCode\ReferralCommissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class OTCService
{
    public function __construct(
        private readonly MarketRepositoryInterface         $marketRepository,
        private readonly ExchangeRepositoryInterface       $exchangeRepository,
        private readonly WalletRepositoryInterface         $walletRepository,
        private readonly TransactionRepositoryInterface    $transactionRepository,
        private readonly OTCOrderRepositoryInterface       $otcOrderRepository,
        private readonly ReferralCommissionService         $referralCommissionService,
        private readonly UserRepositoryInterface           $userRepository,
        private readonly OTCRefExchangeWithdrawalInterface $refExchangeWithdrawalRepository,
    ) {}

    public function markets(): array
    {
        $markets = $this->marketRepository->getOTCMarkets();

        return $markets->map(
            fn(Market $market) => resolve(MarketResponseDTO::class)
                ->setMarketId($market->id)
                ->setBaseCurrency($market->base_currency)
                ->setPrecision($market->currency->precision)
                ->setCurrencyName($market->currency->name)
                ->setCurrencyPersianName($market->currency->persian_name)
                ->setCurrencyLogo($market->currency->logo)
                ->setQuoteCurrency($market->quote_currency)
                ->setIsActive($market->is_active)
                ->setSellPrice(Math::mul($market->exchangePrice->price, (($market->exchangePrice->exchange_profit_buy / 100) + 1)))
                ->setBuyPrice(Math::mul($market->exchangePrice->price, (($market->exchangePrice->exchange_profit_sell / 100) + 1)))
                ->setMinTradeAmount($market->min_trade_amount)
                ->setMaxTradeAmount($market->max_trade_amount)
                ->setMinOTCAmount($market->min_otc_amount)
                ->setMaxOTCAmount($market->max_otc_amount)
        )->toArray();
    }

    public function bitexroomAvailableBalance(int $marketId): string
    {
        $market = $this->marketRepository->getMarketById($marketId);

        $wallet = $this->walletRepository->getOneByCurrency(
            $market->base_currency,
            config('bitexroom.user_id')
        );

        return $wallet->available_balance;
    }

    /**
     * @throws Throwable
     * @throws InsufficientBalanceException
     */
    public function buy(OTCBuyRequestDTO $requestDTO): OTCBuyResponseDTO
    {
        try {

            $user = $this->userRepository->getUserById($requestDTO->getBuyerUserId());
            DB::beginTransaction();
            //Find Market
            $market = $this->marketRepository->getMarketById($requestDTO->getMarketId());
            $activeExchange = $market->exchangePrice->exchange;

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

            $buyAmount = $requestDTO->getQuantity();
            $amountInQuoteCurrency = Math::mul($market->exchangePrice->buy_price, $requestDTO->getQuantity());
            $fee = Math::mul($buyAmount, Math::div(Setting::getSetting('otc_buy_fee'), 100));
            $receivedAmount = Math::sub($buyAmount, $fee);

            if (Math::comp($buyerQuoteWallet->available_balance, $amountInQuoteCurrency) === -1) {
                throw new \App\Exceptions\V1\Wallet\InsufficientBalanceException(__('otc.buyer_insufficient_balance', ['currency' => $market->quote_currency]));
            }

            $otc_order = $this->otcOrderRepository->create(
                resolve(CreateOTCOrderRequestDTO::class)
                    ->setUserId($requestDTO->getBuyerUserId()) // The actual user initiating the transaction
                    ->setMarketId($market->id)
                    ->setQuantity($buyAmount)
                    ->setPrice($market->exchangePrice->buy_price)
                    ->setExchangeId($activeExchange->id)
                    ->setFee($fee)
                    ->setType(OTCOrderTypeEnum::BUY)
                    ->setStatus(OTCOrderStatusEnum::PENDING)
            );

            $doComplete = true;
            if (Math::comp($sellerWallet->available_balance, $receivedAmount) === -1) {

                // $chain = $market->currency->chains->sort(fn($a, $b) => $a->network_fee <=> $b->network_fee)->first();
                // amountForBuy = (receivedAmount - exchange_withdrawal_fee) + network_fee
                //$amountForBuy = Math::sub($receivedAmount, $chain->network_fee);

                $amountForBuy = $receivedAmount; // buy equal user trade
                $exchangeService = resolve(ExchangeService::class);
                $resultBuyRefExchange = $exchangeService->buy(
                    resolve(ExchangeBuyRequestDTO::class)
                        ->setMarketId($requestDTO->getMarketId())
                        ->setOtcId($otc_order->id)
                        ->setQuantity($amountForBuy)
                );

                $doComplete = $resultBuyRefExchange->isDone();
            }

            if ($doComplete) {
                $this->completeBuyOrder(
                    resolve(CompletedOrderRequestDTO::class)
                        ->setOtcId($otc_order->id)
                        ->setBuyerUserId(Auth::id())
                        ->setSellerUserId(config('bitexroom.user_id'))
                );
                if ($otc_order->user->introducer_code) {
                    $this->referralCommissionService->processReferralCommission($otc_order, $fee);
                }
                $user->notify(new OTCBuyCreated($market->base_currency . $market->quote_currency, $requestDTO->getQuantity(), $user->name));
                DB::commit();
            } else {
                if ($resultBuyRefExchange->getSpotStatus() === SpotStatusEnum::NotEnoughBalance) {
                    $exchangeName = $otc_order->exchange->name;
                    $description = 'به علت نداشتن موجودی تتری در ' . $exchangeName . ' سفارش لغو شد.';
                } else {
                    $description = $resultBuyRefExchange->getErrorMessage() . '- Code: ' . $resultBuyRefExchange->getErrorCode();
                }
                $otc_order->update([
                    'status' => OTCOrderStatusEnum::CANCELED,
                    'ref_exchange_description' => $description,
                ]);
                DB::commit();
                throw new BuyTradeWasFiledException(marketName: $market->base_currency . $market->quote_currency);
            }
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();
            throw $exception;
        }

        return resolve(OTCBuyResponseDTO::class)
            ->setOtcOrderModel($otc_order);
    }

    public function completeBuyOrder(CompletedOrderRequestDTO $requestDTO): void
    {
        $otc = $this->otcOrderRepository->getOneById($requestDTO->getOtcId());

        $market = $otc->market;
        $buyAmount = $otc->quantity;
        $buyPrice = $otc->price;

        $amountInQuoteCurrency = Math::mul($buyPrice, $otc->quantity);

        $fee = Math::mul($buyAmount, Math::div(Setting::getSetting('otc_buy_fee'), 100));
        $receivedAmount = Math::sub($buyAmount, $fee);


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

        // Buyer transaction (Base currency)
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId())
                ->setWalletId($buyerWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($buyerWallet->balance)
                ->setAmount($receivedAmount)
                ->setCoinPrice($buyPrice)
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::BUY)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf(
                    'خرید %s %s به قیمت واحد %s %s',
                    formatNumberTrimZeros((float)$buyAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float)$market->exchangePrice->buy_price),
                    $market->quote_currency
                ))
        );

        $buyerWallet->increment('balance', (float)$receivedAmount);

        // Buyer transaction (Quote currency)
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId())
                ->setWalletId($buyerQuoteWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($buyerQuoteWallet->balance)
                ->setAmount((string)-$amountInQuoteCurrency)
                ->setCoinPrice("1")
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::SELL)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'فروش %s %s',
                        formatNumberTrimZeros((float)$amountInQuoteCurrency),
                        $market->quote_currency
                    )
                )
        );
        $buyerQuoteWallet->decrement('balance', (float)$amountInQuoteCurrency);

        // Seller transaction (Quote currency)
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerQuoteWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($sellerQuoteWallet->balance)
                ->setAmount($amountInQuoteCurrency)
                ->setCoinPrice("1")
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::BUY)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'دریافت %s %s ',
                        formatNumberTrimZeros((float)$amountInQuoteCurrency),
                        $market->quote_currency
                    )
                )
        );

        $sellerQuoteWallet->increment('balance', $amountInQuoteCurrency);

        // Seller transaction (Base currency)
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($sellerWallet->balance)
                ->setAmount((string)-$receivedAmount)
                ->setCoinPrice($buyPrice)
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::SELL)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'فروش %s %s به قیمت واحد %s %s',
                        formatNumberTrimZeros((float)$buyAmount),
                        $market->base_currency,
                        formatNumberTrimZeros((float)$market->exchangePrice->buy_price),
                        $market->quote_currency
                    )
                )
        );

        $sellerWallet->decrement('balance', $receivedAmount);

        // 3. Commission Transaction
        $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
            ->setUserId($requestDTO->getSellerUserId())
            ->setWalletId($sellerWallet->id)
            ->setOtcOrderId($otc->id)
            ->setBalance($sellerWallet->balance)
            ->setAmount($otc->fee)
            ->setCoinPrice($buyPrice)
            ->setExchangeId(null)
            ->setType(TransactionTypeEnum::FEE)
            ->setSubtype(TransactionSubTypeEnum::OTC)
            ->setStatus(TransactionStatusEnum::SUCCESS)
            ->setDescription(
                sprintf(
                    'کارمزد معامله %s %s به ارزش %s %s',
                    formatNumberTrimZeros((float)$buyAmount),
                    $market->base_currency,
                    formatNumberTrimZeros((float)$otc->fee),
                    $market->base_currency
                )
            ));
        $sellerWallet->increment('balance', (float)$otc->fee);
        $otc->update([
            'status' => OTCOrderStatusEnum::SUCCESS,
        ]);
    }

    /**
     * @throws Throwable
     * @throws InsufficientBalanceException
     */
    public function sell(OTCSellRequestDTO $requestDTO): OTCBuyResponseDTO
    {
        try {
            $user = $this->userRepository->getUserById($requestDTO->getSellerUserId());
            DB::beginTransaction();
            // Find Market
            $market = $this->marketRepository->getMarketById($requestDTO->getMarketId());
            $activeExchange = $this->exchangeRepository->getActiveExchange();
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

            $sellAmount = $requestDTO->getQuantity();
            $amountInQuoteCurrency = Math::mul($market->exchangePrice->sell_price, $sellAmount);
            $fee = Math::mul($amountInQuoteCurrency, Math::div(Setting::getSetting('otc_sell_fee'), 100));
            $receivedAmount = Math::sub($amountInQuoteCurrency, $fee);

            if (Math::comp($sellerWallet->available_balance, $sellAmount) === -1) {
                throw new InsufficientBalanceException(__('otc.seller_insufficient_balance', ['currency' => $market->base_currency]));
            }

            $otc_order = $this->otcOrderRepository->create(
                resolve(CreateOTCOrderRequestDTO::class)
                    ->setUserId($requestDTO->getSellerUserId())
                    ->setMarketId($market->id)
                    ->setQuantity($sellAmount)
                    ->setPrice($market->exchangePrice->sell_price)
                    ->setFee($fee)
                    ->setType(OTCOrderTypeEnum::SELL)
                    ->setStatus(OTCOrderStatusEnum::PENDING)
            );

            //WITHDRAW USDT From Ref exchange IN SELL
            if (Math::comp($buyerQuoteWallet->available_balance, $receivedAmount) === -1) {

                $usdtWallet = $this->walletRepository
                    ->getBitexroomWallet(
                        'USDT'
                    );

                $chargeUSDTTransaction = $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                    ->setUserId(config('bitexroom.user_id'))
                    ->setWalletId($usdtWallet->id)
                    ->setOtcOrderId($otc_order->id)
                    ->setAmount($receivedAmount)
                    ->setCoinPrice("1")
                    ->setExchangeId($activeExchange->id)
                    ->setType(TransactionTypeEnum::REF_EXCHANGE)
                    ->setSubtype(TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription(
                        sprintf(
                            'برداشت USDT به مقدار %s از صرافی مرجع (%s)',
                            formatNumberTrimZeros((float)$receivedAmount),
                            $market->exchangePrice->exchange->name
                        )
                    ));

                $this->refExchangeWithdrawalRepository->create(
                    resolve(CreateOTCRefExchangeWithdrawalRequestDTO::class)
                        ->setCurrencyId($market->quoteCurrency->id)
                        ->setTransactionId($chargeUSDTTransaction->id)
                        ->setStatus(OTCRefExchangeWithdrawalStatusEnum::PENDING)
                );
            }

            $this->completeSellOrder(
                resolve(CompletedOrderRequestDTO::class)
                    ->setOtcId($otc_order->id)
                    ->setBuyerUserId(config('bitexroom.user_id'))
                    ->setSellerUserId(Auth::id())
            );
            if ($otc_order->user->introducer_code) {
                $this->referralCommissionService->processReferralCommission($otc_order, $fee);
            }
            $user->notify(new OTCSellCreated($market->base_currency . $market->quote_currency, $requestDTO->getQuantity(), $user->name));

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            throw $exception;
        }

        return resolve(OTCBuyResponseDTO::class)
            ->setOtcOrderModel($otc_order);
    }

    public function completeSellOrder(CompletedOrderRequestDTO $requestDTO): void
    {
        $otc = $this->otcOrderRepository->getOneById($requestDTO->getOtcId());

        $market = $otc->market;
        $sellAmount = $otc->quantity;
        $sellPrice = $otc->price;
        $fee = $otc->fee;

        $amountInQuoteCurrency = Math::mul($sellPrice, $sellAmount);

        $receivedAmount = Math::sub($amountInQuoteCurrency, $fee);

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

        // Seller transaction (Base currency)
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($sellerWallet->balance)
                ->setAmount((string)(-$sellAmount))
                ->setCoinPrice($sellPrice)
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::SELL)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'فروش %s %s به قیمت واحد %s %s',
                        formatNumberTrimZeros((float)$sellAmount),
                        $market->base_currency,
                        formatNumberTrimZeros((float)$market->exchangePrice->sell_price),
                        $market->quote_currency
                    )
                )
        );
        $sellerWallet->decrement('balance', (float)$sellAmount);

        // Seller transaction (Quote currency)
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getSellerUserId())
                ->setWalletId($sellerQuoteWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($sellerQuoteWallet->balance)
                ->setAmount($receivedAmount)
                ->setCoinPrice("1")
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::BUY)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'دریافت %s %s به ازای فروش %s %s',
                        formatNumberTrimZeros((float)$receivedAmount),
                        $market->quote_currency,
                        formatNumberTrimZeros((float)$sellAmount),
                        $market->base_currency
                    )
                )
        );
        $sellerQuoteWallet->increment('balance', (float)$receivedAmount);

        // Buyer transaction (Base currency)
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId())
                ->setWalletId($buyerWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($buyerWallet->balance)
                ->setAmount($sellAmount)
                ->setCoinPrice($sellPrice)
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::BUY)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'خرید %s %s به قیمت %s %s',
                        formatNumberTrimZeros((float)$sellAmount),
                        $market->base_currency,
                        formatNumberTrimZeros((float)$market->exchangePrice->sell_price),
                        $market->quote_currency,
                    )
                )
        );
        $buyerWallet->increment('balance', (float)$sellAmount);

        // Buyer transaction (Quote currency)
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId($requestDTO->getBuyerUserId())
                ->setWalletId($buyerQuoteWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($buyerQuoteWallet->balance)
                ->setAmount((string)(-$receivedAmount))
                ->setCoinPrice("1")
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::SELL)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'خرید %s %s به قیمت واحد %s %s',
                        formatNumberTrimZeros((float)$sellAmount),
                        $market->base_currency,
                        formatNumberTrimZeros((float)$market->exchangePrice->sell_price),
                        $market->quote_currency
                    )
                )
        );
        if (Math::comp($buyerQuoteWallet->available_balance, $receivedAmount) >= 0) {
            $buyerQuoteWallet->decrement('balance', (float)$receivedAmount);
        }


        // Commission Transaction
        $this->transactionRepository->create(
            resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.user_id'))
                ->setWalletId($buyerQuoteWallet->id)
                ->setOtcOrderId($otc->id)
                ->setBalance($buyerQuoteWallet->balance)
                ->setAmount($fee)
                ->setCoinPrice("1")
                ->setExchangeId(null)
                ->setType(TransactionTypeEnum::FEE)
                ->setSubtype(TransactionSubTypeEnum::OTC)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'کارمزد معامله %s %s به ارزش %s %s',
                        formatNumberTrimZeros((float)$sellAmount),
                        $market->base_currency,
                        formatNumberTrimZeros((float)$fee),
                        $market->quote_currency
                    )
                )
        );
        $buyerQuoteWallet->increment('balance', (float)$fee);
        $otc->update([
            'status' => OTCOrderStatusEnum::SUCCESS,
        ]);
    }
}
