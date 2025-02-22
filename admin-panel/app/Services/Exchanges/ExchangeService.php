<?php

namespace App\Services\Exchanges;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\Transaction;
use App\Models\WalletChain;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;
use App\Services\Exchanges\DTO\ChargeUSDTRequestDTO;
use App\Services\Exchanges\DTO\ChargeUSDTResponse;
use App\Services\Wallet\WalletService;
use Throwable;

class ExchangeService
{
    public function __construct(private readonly WalletService $walletService) {}

    public function chargeCurrency(ChargeUSDTRequestDTO $requestDTO): ChargeUSDTResponse
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

            $cetWallet = $this->walletService->getExchangeWallet('CET');
            // CET
            Transaction::query()->create([
                'user_id' => config('exchange.exchange_user_id'),
                'wallet_id' => $cetWallet->id,
                'amount' => -$response->getFee(),
                'type' => TransactionTypeEnum::EXCHANGE,
                'subtype' => TransactionSubTypeEnum::COINEX,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => sprintf('استفاده CET به مقدار %s',
                    formatNumberTrimZeros((float) $response->getFee())
                ),
            ]);
            if ($requestDTO->getCurrency() === 'USDT') {
                $currencyWallet = $this->walletService->getExchangeWallet($requestDTO->getCurrency());
                // USDT
                $currencyWallet->increment('balance', (float) $response->getActualAmount());
            }

        } catch (Throwable $exception) {
            report($exception);

            return resolve(ChargeUSDTResponse::class)
                ->setWithdrawStatus(WithdrawStatusEnum::FAILED);
        }

        return resolve(ChargeUSDTResponse::class)
            ->setWithdrawStatus($response->getStatus());
    }
}
