<?php

namespace App\Services\Currency;

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
            ->setChains($model->chains->map(fn ($item) => resolve(ChainResponseDTO::class)
                ->setChain($item->chain)
                ->setMinDepositAmount($item->min_deposit_amount)
                ->setMinWithdrawAmount($item->min_withdraw_amount)
                ->setDepositEnabled($item->deposit_enabled)
                ->setWithdrawEnabled($item->withdraw_enabled)
                ->setDepositDelayMinutes($item->deposit_delay_minutes)
                ->setSafeConfirmations($item->safe_confirmations)
                ->setWithdrawalFee(bcadd($item->exchange_fee, $item->network_fee, config('bitexroom.scale_precision')))
                ->setWithdrawPrecision($item->withdrawal_precision)
                ->setMemo($item->memo)
                ->setIsMemoRequiredForDeposit($item->is_memo_required_for_deposit)
            )->toArray());
    }
}
