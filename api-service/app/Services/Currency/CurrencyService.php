<?php

namespace App\Services\Currency;

use App\Helpers\Math;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Repositories\DTO\Currency\ChainResponseDTO;
use App\Repositories\DTO\Currency\GetConfigResponseDTO;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Services\Currency\DTO\GetConfigCurrencyRequestDTO;

class CurrencyService
{
    public function __construct(private readonly CurrencyRepositoryInterface $currencyRepository) {}

    public function getConfig(GetConfigCurrencyRequestDTO $requestDTO): GetConfigResponseDTO
    {
        $model = $this->currencyRepository
            ->getCurrencyWithChains($requestDTO->getSymbol());

        return resolve(GetConfigResponseDTO::class)
            ->setName($model->name)
            ->setSymbol($model->symbol)
            ->setInterTransferEnabled($model->inter_transfer_enabled)
            ->setChains($model->chains->map(
                fn($item) => resolve(ChainResponseDTO::class)
                    ->setChain($item->chain)
                    ->setMinDepositAmount($item->min_deposit_amount)
                    ->setMinWithdrawAmount($item->min_withdraw_amount)
                    ->setDepositEnabled($item->deposit_enabled)
                    ->setWithdrawEnabled($item->withdraw_enabled)
                    ->setDepositDelayMinutes($item->deposit_delay_minutes)
                    ->setSafeConfirmations($item->safe_confirmations)
                    ->setWithdrawalFee(Math::add($item->exchange_withdrawal_fee, $item->network_fee))
                    ->setWithdrawPrecision($item->withdrawal_precision)
                    ->setMemo($item->memo)
                    ->setIsMemoRequiredForDeposit($item->is_memo_required_for_deposit)
            )->toArray());
    }

    public function getAllConfig(): array
    {
        $allCurrencies = $this->currencyRepository
            ->getAllCurrencyWithChains();

        return $allCurrencies->map(fn(Currency $model) => resolve(GetConfigResponseDTO::class)
            ->setName($model->name)
            ->setPrecision($model->precision)
            ->setSymbol($model->symbol)
            ->setMaxAutoWithdrawAmount($model->max_auto_withdraw_amount)
            ->setCurrencyLogo($model->logo)
            ->setInterTransferEnabled($model->inter_transfer_enabled)
            ->setChains($model->chains->map(
                fn(CurrencyChain $item) => resolve(ChainResponseDTO::class)
                    ->setChain($item->chain)
                    ->setChainName($item->chain_name)
                    ->setMinDepositAmount($item->min_deposit_amount)
                    ->setMinWithdrawAmount($item->min_withdraw_amount)
                    ->setDepositEnabled($item->deposit_enabled)
                    ->setWithdrawEnabled($item->withdraw_enabled)
                    ->setDepositDelayMinutes($item->deposit_delay_minutes)
                    ->setSafeConfirmations($item->safe_confirmations)
                    ->setWithdrawalFee(Math::add($item->exchange_withdrawal_fee ?? 0, $item->network_fee ?? 0))
                    ->setWithdrawPrecision($item->withdrawal_precision)
                    ->setMemo($item->memo)
                    ->setIsMemoRequiredForDeposit($item->is_memo_required_for_deposit)
            )->toArray()))->toArray();
    }
}
