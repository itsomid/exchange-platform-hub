<?php

namespace App\Services\Exchanges;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\ExchangeAssetsWithdrawal;
use App\Repositories\DTO\OTCRefExchangeWithdrawal\CreateOTCRefExchangeWithdrawalRequestDTO;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;
use App\Repositories\Interfaces\OTCRefExchangeWithdrawalInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WalletChainRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;
use App\Services\Exchanges\DTO\ChargeUSDTRequestDTO;
use App\Services\Exchanges\DTO\ChargeUSDTResponse;
use App\Services\Exchanges\DTO\ExchangeBuyRequestDTO;
use App\Services\Exchanges\DTO\ExchangeBuyResponseDTO;
use Throwable;

class ExchangeService
{
    public function __construct(
        private readonly MarketRepositoryInterface         $marketRepository,
        private readonly TransactionRepositoryInterface    $transactionRepository,
        private readonly OTCOrderRepositoryInterface       $otcOrderRepository,
        private readonly WalletRepositoryInterface         $walletRepository,
        private readonly WalletChainRepositoryInterface    $chainRepository,
        private readonly OTCRefExchangeWithdrawalInterface $refExchangeWithdrawalRepository,
    ) {}

    public function buy(ExchangeBuyRequestDTO $requestDTO): ExchangeBuyResponseDTO
    {
        $market = $this->marketRepository->getMarketById($requestDTO->getMarketId());
        $exchangeName = $market->exchangePrice->exchange->slug;
        $asset = AssetFactory::make($exchangeName);

        $response = $asset->placeOrder(
            resolve(BuyDTORequest::class)
                ->setSide('buy')
                ->setMarket($market->base_currency . $market->quote_currency)
                ->setMarketType('SPOT')
                ->setQuantity($requestDTO->getQuantity())
                ->setOrderType('market')
                ->setCurrency($market->base_currency)
        );

        if ($response->isDone()) {
            $otcOrder = $this->otcOrderRepository->getOneById($requestDTO->getOtcId());
            $otcOrder->refExchangeTransactions()->create([
                'order_id' => $response->getOrderId(),
                'exchange_id' => $market->exchangePrice->exchange->id,
                'market' => $response->getMarket(),
                'currency_symbol' => $response->getCurrencySymbol(),
                'amount' => $response->getAmount(),
                'fee' => $response->getDiscountFee(),
                'filled_amount' => $response->getFilledAmount(),
                'side' => 'buy',
                'response' => $response->getResponseBody(),
            ]);

            // Get fee currency based on exchange type
            $feeCurrency = $this->getFeeCurrencyForExchange($exchangeName);

            $feeWallet = $this->walletRepository
                ->getOrCreateWallet(
                    config('bitexroom.user_id'),
                    $feeCurrency
                );
            $usdtWallet = $this->walletRepository
                ->getOrCreateWallet(
                    config('bitexroom.user_id'),
                    'USDT'
                );
            $baseCurrencyWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->base_currency,
                    config('bitexroom.user_id')
                );

            // Get fee currency market price
            $feeMarket = $this->marketRepository->getMarketBySymbol($feeCurrency, 'USDT');
            $feePrice = $feeMarket ? $feeMarket->exchangePrice->price : 0;

            // Create fee transaction if there's a fee
            if ((float)$response->getDiscountFee() > 0) {
                $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                    ->setUserId(config('bitexroom.user_id'))
                    ->setWalletId($feeWallet->id)
                    ->setOtcOrderId($otcOrder->id)
                    ->setAmount(-$response->getDiscountFee())
                    ->setCoinPrice($feeCurrency === 'USDT' ? "1" : $feePrice)
                    ->setExchangeId($market->exchangePrice->exchange->id)
                    ->setType(TransactionTypeEnum::REF_EXCHANGE)
                    ->setSubtype(TransactionSubTypeEnum::REF_EXCHANGE_BUY_FEE)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription(
                        sprintf(
                            'استفاده %s به مقدار %s برای فی خرید از صرافی مرجع (%s)',
                            $feeCurrency,
                            formatNumberTrimZeros((float)$response->getDiscountFee()),
                            $market->exchangePrice->exchange->name
                        ),
                    ));
            }

            //USDT - adjust amount if fee currency is USDT to avoid double counting
            $usdtAmount = $response->getFilledValue();
            if ($feeCurrency === 'USDT' && (float)$response->getDiscountFee() > 0) {
                // If fee is paid in USDT, subtract it from the main transaction to avoid double counting
                $usdtAmount = bcsub($usdtAmount, $response->getDiscountFee(), 8);
            }

            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.user_id'))
                ->setWalletId($usdtWallet->id)
                ->setOtcOrderId($otcOrder->id)
                ->setAmount(-$usdtAmount)
                ->setCoinPrice("1")
                ->setExchangeId($market->exchangePrice->exchange->id)
                ->setType(TransactionTypeEnum::REF_EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::REF_EXCHANGE_BUY)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'استفاده USDT به مقدار %s در خرید از صرافی مرجع (%s)',
                        formatNumberTrimZeros((float)$usdtAmount),
                        $market->exchangePrice->exchange->name
                    ),
                ));
            //BASE Currency
            $baseCurrencyTransaction = $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.user_id'))
                ->setWalletId($baseCurrencyWallet->id)
                ->setOtcOrderId($otcOrder->id)
                ->setAmount($response->getFilledAmount())
                ->setCoinPrice($market->exchangePrice->price)
                ->setExchangeId($market->exchangePrice->exchange->id)
                ->setType(TransactionTypeEnum::REF_EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::REF_EXCHANGE_BUY)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'خرید %s به مقدار %s از صرافی مرجع (%s)',
                        $market->base_currency,
                        formatNumberTrimZeros((float)$response->getAmount()),
                        $market->exchangePrice->exchange->name
                    ),
                ));
            $baseCurrencyWallet->increment('balance', (float)$response->getAmount());

            $this->refExchangeWithdrawalRepository->create(
                resolve(CreateOTCRefExchangeWithdrawalRequestDTO::class)
                    ->setCurrencyId($market->currency->id)
                    ->setTransactionId($baseCurrencyTransaction->id)
                    ->setStatus(OTCRefExchangeWithdrawalStatusEnum::PENDING)
            );
        }

        return resolve(ExchangeBuyResponseDTO::class)
            ->setIsDone($response->isDone())
            ->setErrorMessage($response->getErrorMessage())
            ->setErrorCode($response->getErrorCode())
            ->setSpotStatus($response->getSpotStatus());
    }


    public function chargeUSDT(ChargeUSDTRequestDTO $requestDTO): ChargeUSDTResponse
    {
        $cetMarket = $this->marketRepository->getMarketBySymbol('CET', 'USDT');
        try {
            $asset = AssetFactory::make('coinex');

            $bitexroomWallet = $this->walletRepository->getBitexroomWallet('USDT');
            $chain = $this->chainRepository->createOrGetChain($bitexroomWallet->id, $requestDTO->getCurrencyChain());
            $response = $asset->withdraw(
                resolve(WithdrawRequestDTO::class)
                    ->setAddress($chain->address)
                    ->setChain($requestDTO->getCurrencyChain())
                    ->setAmount($requestDTO->getQuantity())
                    ->setWithdrawMethod(WithdrawMethodEnum::ON_CHAIN)
                    ->setCurrency($bitexroomWallet->currency_symbol)
            );

            ExchangeAssetsWithdrawal::query()
                ->create([
                    'withdrawal_id' => $response->getWithdrawId(),
                    'exchange' => $cetMarket->exchangePrice->exchange->slug,
                    'currency_symbol' => $bitexroomWallet->currency_symbol,
                    'currency_chain' => $requestDTO->getCurrencyChain(),
                    'fee_currency' => $response->getCurrencyFee(),
                    'fee' => $response->getFee(),
                    'amount' => $response->getAmount(),
                    'actual_amount' => $response->getActualAmount(),
                    'hd_wallet_address' => $response->getAddress(),
                    'withdrawal_date' => $response->getCreatedAt(),
                    'explore_address_url' => $response->getExploreAddress(),
                ]);

            $cetWallet = $this->walletRepository
                ->getOrCreateWallet(
                    config('bitexroom.user_id'),
                    'CET'
                );
            $exchangeUSDTWallet = $this->walletRepository
                ->getOrCreateWallet(
                    config('bitexroom.user_id'),
                    'USDT'
                );


            $cetPrice = $cetMarket ? $cetMarket->exchangePrice->price : 0;
            //CET
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.user_id'))
                ->setWalletId($cetWallet->id)
                ->setAmount(-$response->getFee())
                ->setCoinPrice($cetPrice)
                ->setExchangeId($cetMarket->exchangePrice->exchange->id)
                ->setType(TransactionTypeEnum::REF_EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::REF_EXCHANGE_BUY_FEE)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'استفاده CET به مقدار %s',
                        formatNumberTrimZeros((float)$response->getFee())
                    )
                ));
            //USDT
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.user_id'))
                ->setWalletId($exchangeUSDTWallet->id)
                ->setAmount(-$response->getActualAmount())
                ->setCoinPrice("1")
                ->setExchangeId($cetMarket->exchangePrice->exchange->id)
                ->setType(TransactionTypeEnum::REF_EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::REF_EXCHANGE_BUY)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(
                    sprintf(
                        'استفاده USDT به مقدار %s',
                        formatNumberTrimZeros((float)$response->getActualAmount())
                    )
                ));
            $exchangeUSDTWallet->increment('balance', (float)$response->getActualAmount());
        } catch (Throwable $exception) {
            report($exception);

            return resolve(ChargeUSDTResponse::class)
                ->setWithdrawStatus(WithdrawStatusEnum::FAILED);
        }

        return resolve(ChargeUSDTResponse::class)
            ->setWithdrawStatus($response->getStatus());
    }


    /**
     * Get fee currency for different exchanges
     */
    private function getFeeCurrencyForExchange(string $exchangeName): string
    {
        return config("exchanges.{$exchangeName}.fee_currency", 'USDT');
    }
}
