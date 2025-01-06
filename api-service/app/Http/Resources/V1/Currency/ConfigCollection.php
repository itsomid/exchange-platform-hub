<?php

namespace App\Http\Resources\V1\Currency;

use App\Repositories\DTO\Currency\ChainResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *      schema="ConfigCollection",
 *      type="object",
 *
 *      @OA\Property(
 *          property="asset",
 *          type="object",
 *          @OA\Property(property="ccy", type="string", example="BTC", description="Currency symbol."),
 *          @OA\Property(property="inter_transfer_enabled", type="boolean", example=true, description="Whether inter-transfer is enabled for this currency.")
 *      ),
 *      @OA\Property(
 *          property="chains",
 *          type="array",
 *
 *          @OA\Items(
 *              type="object",
 *
 *              @OA\Property(property="chain", type="string", example="BTC", description="Chain name."),
 *              @OA\Property(property="min_deposit_amount", type="number", format="float", example=0.0001, description="Minimum deposit amount."),
 *              @OA\Property(property="min_withdraw_amount", type="number", format="float", example=0.001, description="Minimum withdrawal amount."),
 *              @OA\Property(property="deposit_enabled", type="boolean", example=true, description="Whether deposits are enabled."),
 *              @OA\Property(property="withdraw_enabled", type="boolean", example=true, description="Whether withdrawals are enabled."),
 *              @OA\Property(property="deposit_delay_minutes", type="integer", example=10, description="Delay in minutes for deposits."),
 *              @OA\Property(property="safe_confirmations", type="integer", example=6, description="Number of safe confirmations."),
 *              @OA\Property(property="irreversible_confirmations", type="integer", example=10, description="Number of irreversible confirmations."),
 *              @OA\Property(property="withdrawal_fee", type="number", format="float", example=0.0005, description="Withdrawal fee."),
 *              @OA\Property(property="withdrawal_precision", type="integer", example=8, description="Precision for withdrawal amounts."),
 *              @OA\Property(property="memo", type="string", nullable=true, example="Required for some transactions.", description="Additional memo information for the chain.")
 *          )
 *      )
 *  )
 */
class ConfigCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn ($configResponseDTO) => [
            'asset' => [
                'ccy' => $configResponseDTO->getSymbol(),
                'inter_transfer_enabled' => $configResponseDTO->getInterTransferEnabled(),
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
            }, $configResponseDTO->getChains()),
        ])->toArray();
    }
}
