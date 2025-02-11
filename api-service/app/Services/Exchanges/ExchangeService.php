<?php

namespace App\Services\Exchanges;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\ExchangeAssetsWithdrawal;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;
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
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly OTCOrderRepositoryInterface $otcOrderRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly WalletChainRepositoryInterface $chainRepository
    ) {}

    public function buy(ExchangeBuyRequestDTO $requestDTO): ExchangeBuyResponseDTO
    {
        $market = $this->marketRepository->getMarketById($requestDTO->getMarketId());
        $exchangeName = $market->exchangePrice->exchange->slug;
        $asset = AssetFactory::make($exchangeName);

        $response = $asset->placeOrder(
            resolve(BuyDTORequest::class)
                ->setSide('buy')
                ->setMarket($market->base_currency.$market->quote_currency)
                ->setMarketType('SPOT')
                ->setQuantity($requestDTO->getQuantity())
                ->setOrderType('market')
                ->setCurrency($market->base_currency)
        );
        if ($response->isDone()) {
            $otcOrder = $this->otcOrderRepository->getOneById($requestDTO->getOtcId());
            $otcOrder->refExchangeTransactions()->create([
                'order_id' => $response->getOrderId(),
                'market' => $response->getMarket(),
                'amount' => $response->getAmount(),
                'fee' => $response->getDiscountFee(),
                'side' => 'buy',
                'response' => $response->getResponseBody(),
            ]);

            $cetWallet = $this->walletRepository
                ->getOrCreateWallet(
                    config('bitexroom.bitexroom_user_id'),
                    'CET'
                );
            $usdtWallet = $this->walletRepository
                ->getOrCreateWallet(
                    config('bitexroom.bitexroom_user_id'),
                    'USDT'
                );
            $baseCurrencyWallet = $this->walletRepository
                ->getOneOrCreateByCurrencyWithLock(
                    $market->base_currency,
                    config('bitexroom.bitexroom_user_id')
                );
            //CET
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.bitexroom_user_id'))
                ->setWalletId($cetWallet->id)
                ->setOtcOrderId($otcOrder->id)
                ->setBalance($cetWallet->balance)
                ->setAmount(-$response->getDiscountFee())
                ->setType(TransactionTypeEnum::EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::COINEX)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('استفاده CET به مقدار %s',
                    formatNumberTrimZeros((float) $response->getDiscountFee())
                )
                ));
            //USDT
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.bitexroom_user_id'))
                ->setWalletId($usdtWallet->id)
                ->setOtcOrderId($otcOrder->id)
                ->setBalance($usdtWallet->balance)
                ->setAmount(-$response->getFilledValue())
                ->setType(TransactionTypeEnum::EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::COINEX)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('استفاده USDT به مقدار %s',
                    formatNumberTrimZeros((float) $response->getFilledValue())
                )
                ));
            //BASE Currency
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.bitexroom_user_id'))
                ->setWalletId($baseCurrencyWallet->id)
                ->setOtcOrderId($otcOrder->id)
                ->setAmount($response->getAmount())
                ->setBalance($baseCurrencyWallet->balance)
                ->setType(TransactionTypeEnum::EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::COINEX)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('خرید %s به مقدار %s',
                    $market->base_currency,
                    formatNumberTrimZeros((float) $response->getAmount()),
                )
                ));
            $baseCurrencyWallet->increment('balance', (float) $response->getAmount());

        }

        return resolve(ExchangeBuyResponseDTO::class)
            ->setIsDone($response->isDone())
            ->setErrorCode($response->getErrorCode())
            ->setSpotStatus($response->getSpotStatus());
    }

    public function chargeUSDT(ChargeUSDTRequestDTO $requestDTO): ChargeUSDTResponse
    {
        //        $otcOrder->refExchangeTransactions()->create([
        //            'market' => $response->getMarket(),
        //            'amount' => $response->getAmount(),
        //            'fee' => $response->getDiscountFee(),
        //            'side' => 'buy',
        //            'response' => $response->getResponseBody(),
        //        ]);

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
                    'exchange' => 'coinex',
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

            $otcOrder = $this->otcOrderRepository->getOneById($requestDTO->getOtcId());
            $cetWallet = $this->walletRepository
                ->getOrCreateWallet(
                    config('bitexroom.bitexroom_user_id'),
                    'CET'
                );
            $usdtWallet = $this->walletRepository
                ->getOrCreateWallet(
                    config('bitexroom.bitexroom_user_id'),
                    'USDT'
                );
            //CET
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.bitexroom_user_id'))
                ->setWalletId($cetWallet->id)
                ->setOtcOrderId($otcOrder->id)
                ->setBalance($cetWallet->balance)
                ->setAmount(-$response->getFee())
                ->setType(TransactionTypeEnum::EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::COINEX)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('استفاده CET به مقدار %s',
                    formatNumberTrimZeros((float) $response->getFee())
                )
                ));
            //USDT
            $this->transactionRepository->create(resolve(CreateTransactionRequestDTO::class)
                ->setUserId(config('bitexroom.bitexroom_user_id'))
                ->setWalletId($usdtWallet->id)
                ->setOtcOrderId($otcOrder->id)
                ->setAmount($response->getActualAmount())
                ->setBalance($usdtWallet->balance)
                ->setType(TransactionTypeEnum::EXCHANGE)
                ->setSubtype(TransactionSubTypeEnum::COINEX)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setDescription(sprintf('خرید %s به مقدار %s',
                    'USDT',
                    formatNumberTrimZeros((float) $response->getActualAmount()),
                )
                ));
            $usdtWallet->increment('balance', (float) $response->getActualAmount());
        } catch (Throwable $exception) {
            report($exception);

            return resolve(ChargeUSDTResponse::class)
                ->setWithdrawStatus(WithdrawStatusEnum::FAILED);
        }

        return resolve(ChargeUSDTResponse::class)
            ->setWithdrawStatus($response->getStatus());
    }
}
