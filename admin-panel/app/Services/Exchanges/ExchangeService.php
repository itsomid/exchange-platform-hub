<?php

namespace App\Services\Exchanges;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Exchange\CoinexWithdrawalException;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\Transaction;
use App\Models\WalletChain;
use App\Repositories\ExchangeRepository;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use App\Services\Exchanges\DTO\ChargeCurrencyRequestDTO;
use App\Services\Exchanges\DTO\ChargeCurrencyResponseDTO;
use App\Services\Wallet\WalletService;
use Throwable;

class ExchangeService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly ExchangeRepository $exchangeRepository,
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    public function chargeCurrency(ChargeCurrencyRequestDTO $requestDTO): ChargeCurrencyResponseDTO
    {
        try {
            
            if ($requestDTO->getExchangeSlug()) {
                $exchange = $this->exchangeRepository->getExchangeBySlug($requestDTO->getExchangeSlug());
            } else {
                $exchange = $this->exchangeRepository->getActiveExchange();
            }

            $asset = AssetFactory::make($exchange->slug);

            $bitexroomWallet = $this->walletService->getExchangeWallet($requestDTO->getCurrency());

            $chain = WalletChain::query()
                ->firstOrCreate(
                    [
                        'wallet_id' => $bitexroomWallet->id,
                        'currency_chain' => $requestDTO->getCurrencyChain(),
                    ]
                );

            $response = $asset->withdraw(
                resolve(WithdrawRequestDTO::class)
                    ->setAddress($chain->address)
                    ->setChain($requestDTO->getCurrencyChain())
                    ->setAmount($requestDTO->getQuantity())
                    ->setWithdrawMethod(WithdrawMethodEnum::ON_CHAIN)
                    ->setCurrency($bitexroomWallet->currency_symbol)
            );

            // Continue with successful withdrawal processing
            ExchangeAssetsWithdrawal::query()
                ->create([
                    'withdrawal_id' => $response->getWithdrawId(),
                    'exchange' => $exchange->slug,
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


            $feeCurrency = $response->getCurrencyFee();
            $baseCurrency = $bitexroomWallet->currency_symbol;

            $feeCurrencyWallet = $this->walletService->getExchangeWallet($feeCurrency);

            $baseMarket = $this->marketRepository->getMarketBySymbol($baseCurrency, 'USDT');
            $feeMarket = $this->marketRepository->getMarketBySymbol($feeCurrency, 'USDT');



            //Base Currency
            Transaction::query()->create([
                'user_id' => config('bitexroom.user_id'),
                'wallet_id' => $bitexroomWallet->id,
                'amount' => $response->getAmount(),
                'coin_price' =>  $baseMarket ? $baseMarket->activeExchangePrice->price : 1,
                'exchange_id' => $exchange->id,
                'type' => TransactionTypeEnum::ًREF_EXCHANGE,
                'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => sprintf(
                    'برداشت از صرافی مرجع (%s) به مقدار %s',
                    $exchange->name,
                    formatNumberTrimZeros((float) $response->getAmount())
                ),
            ]);

            // Create fee transaction if there's a fee
            if ((float)$response->getFee() > 0) {
                Transaction::query()->create([
                    'user_id' => config('bitexroom.user_id'),
                    'wallet_id' => $feeCurrencyWallet->id,
                    'amount' => -$response->getFee(),
                    'coin_price' =>  $feeMarket ? $feeMarket->activeExchangePrice->price : 0,
                    'exchange_id' => $exchange->id,
                    'type' => TransactionTypeEnum::ًREF_EXCHANGE,
                    'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL_FEE,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => sprintf(
                        'استفاده %s به مقدار %s برای برداشت از صرافی مرجع (%s)',
                        $feeCurrency,
                        formatNumberTrimZeros((float) $response->getFee()),
                        $exchange->name
                    ),
                ]);
            }
        } catch (CoinexWithdrawalException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report("sss:" . $exception);
            throw $exception;
        }

        return resolve(ChargeCurrencyResponseDTO::class)
            ->setWithdrawStatus($response->getStatus());
    }
}
