<?php

namespace App\Services\Exchanges;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Exchange\CoinexWithdrawalException;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\Transaction;
use App\Models\WalletChain;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;
use App\Services\Exchanges\DTO\ChargeUSDTRequestDTO;
use App\Services\Exchanges\DTO\ChargeUSDTResponseDTO;
use App\Services\Wallet\WalletService;
use Throwable;

class ExchangeService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly MarketRepositoryInterface $marketRepository,
    ) {}

    public function chargeCurrency(ChargeUSDTRequestDTO $requestDTO): ChargeUSDTResponseDTO
    {
        try {
            $asset = AssetFactory::make('coinex');

            $bitexroomWallet = $this->walletService->getExchangeWallet($requestDTO->getCurrency());

            $chain = WalletChain::query()
                ->firstOrCreate(
                    [
                        'wallet_id' => $bitexroomWallet->id,
                        'currency_chain' => $requestDTO->getCurrencyChain(),
                    ]);

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

            $baseCurrencyWallet = $this->walletService->getExchangeWallet($bitexroomWallet->currency_symbol);
            $cetWallet = $this->walletService->getExchangeWallet('CET');

            $market = $this->marketRepository->getMarketBySymbol($bitexroomWallet->currency_symbol,'USDT');
            $cetMarket = $this->marketRepository->getMarketBySymbol('CET','USDT');
            $cetPrice = $cetMarket ? $cetMarket->activeExchangePrice->price : 0;
            // CET
            Transaction::query()->create([
                'user_id' => config('exchange.exchange_user_id'),
                'wallet_id' => $cetWallet->id,
                'amount' => $response->getFee(),
                'coin_price' =>  $cetPrice,
                'exchange_id' => $market->activeExchange->id,
                'type' => TransactionTypeEnum::ًREF_EXCHANGE,
                'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL_FEE,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => sprintf('استفاده CET به مقدار %s برای برداشت از صرافی مرجع (%s)',
                    formatNumberTrimZeros((float) $response->getFee()),
                    $market->activeExchange->name
                ),
            ]);
            //Base Currency
            Transaction::query()->create([
                'user_id' => config('exchange.exchange_user_id'),
                'wallet_id' => $baseCurrencyWallet->id,
                'amount' => $response->getAmount(),
                'coin_price' =>  $market->activeExchangePrice->price,
                'exchange_id' => $market->activeExchange->id,
                'type' => TransactionTypeEnum::ًREF_EXCHANGE,
                'subtype' => TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => sprintf('برداشت از صرافی مرجع (%s) به مقدار %s',
                    $market->activeExchange->name,
                    formatNumberTrimZeros((float) $response->getAmount())
                ),
            ]);
            if ($requestDTO->getCurrency() === 'USDT') {
                $currencyWallet = $this->walletService->getExchangeWallet($requestDTO->getCurrency());
                // USDT
                $currencyWallet->increment('balance', (float) $response->getActualAmount());
            }

        } catch (CoinexWithdrawalException $exception) {
            throw $exception;
        }catch (Throwable $exception) {
            report("sss:".$exception);
            throw $exception;
        }

        return resolve(ChargeUSDTResponseDTO::class)
            ->setWithdrawStatus($response->getStatus());
    }
}
