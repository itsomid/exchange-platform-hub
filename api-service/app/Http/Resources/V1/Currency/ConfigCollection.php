<?php

namespace App\Http\Resources\V1\Currency;

use App\Repositories\DTO\Currency\ChainResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;


class ConfigCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn($configResponseDTO) => [
            'asset' => [
                'ccy' => $configResponseDTO->getSymbol(),
                'currency_name' => $configResponseDTO->getCurrencyName(),
                'currency_persian_name' => $configResponseDTO->getCurrencyPersianName(),
                'currency_logo' => $configResponseDTO->getCurrencyLogo() ? config('bitexroom.currency_logo_base_url') . '/' . $configResponseDTO->getCurrencyLogo() : null,
                'inter_transfer_enabled' => $configResponseDTO->getInterTransferEnabled(),
                'max_auto_withdraw_amount' => $configResponseDTO->getMaxAutoWithdrawAmount(),
                'price_precision' => $configResponseDTO->getPricePrecision(),
                'amount_precision' => $configResponseDTO->getAmountPrecision(),
                'quote_precision' => $configResponseDTO->getQuotePrecision(),
            ],
            'chains' => array_map(function (ChainResponseDTO $chain) {
                return [
                    'chain' => $chain->getChain()->value,
                    'chain_name' => $chain->getChainName(),
                    'min_deposit_amount' => $chain->getMinDepositAmount(),
                    'min_withdraw_amount' => $chain->getMinWithdrawAmount(),
                    'deposit_enabled' => $chain->getDepositEnabled(),
                    'withdraw_enabled' => $chain->getWithdrawEnabled(),
                    'deposit_delay_minutes' => $chain->getDepositDelayMinutes(),
                    'safe_confirmations' => $chain->getSafeConfirmations(),
                    'irreversible_confirmations' => $chain->getDepositDelayMinutes(),
                    'withdrawal_fee' => $chain->getWithdrawalFee(),
                    'withdrawal_precision' => $chain->getWithdrawPrecision(),
                    'memo' => $chain->getMemo(),
                    'contract_address' => $chain->getContractAddress(),
                    'contract_address_url' => $chain->getContractAddressUrl(),
                ];
            }, $configResponseDTO->getChains()),
        ])->toArray();
    }
}
