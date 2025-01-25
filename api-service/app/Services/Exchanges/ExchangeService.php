<?php

namespace App\Services\Exchanges;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\DTO\ExchangeBuyRequestDTO;

class ExchangeService
{
    private AssetInterface $asset;

    public function __construct(
        private MarketRepositoryInterface $marketRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private OTCOrderRepositoryInterface $otcOrderRepository,
        private WalletRepositoryInterface $walletRepository,
    ) {
        $this->asset = AssetFactory::make('coinex');
    }

    public function buy(ExchangeBuyRequestDTO $requestDTO): bool
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
                    number_format((float) $response->getDiscountFee())
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
                    number_format((float) $response->getFilledValue())
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
                    number_format((float) $response->getAmount()),
                )
                ));
            $baseCurrencyWallet->increment('balance', (float) $response->getAmount());

        }

        return $response->isDone();
    }
}
