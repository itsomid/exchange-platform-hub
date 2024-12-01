<?php

namespace App\Http\Resources\V1\Currency;

use App\Repositories\DTO\Currency\ChainResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @method array  getChains()
 * @method string getName()
 * @method bool   getDepositEnabled()
 * @method bool   getWithdrawEnabled()
 * @method bool   getInterTransferEnabled()
 */
class ConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'asset' => [
                'ccy' => $this->getName(),
                'inter_transfer_enabled' => $this->getInterTransferEnabled(),
            ],
            'chains' => array_map(function (ChainResponseDTO $chain) {
                return [
                    'chain' => $chain->getChain(),
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
                ];
            }, $this->getChains()),
        ];
    }
}
