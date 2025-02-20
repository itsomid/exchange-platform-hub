<?php

namespace App\Http\Resources\V1\Wallet;

use App\Helpers\CryptoExplorerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CheckWithdrawalResource",
 *     type="object",
 *     title="Check Withdrawal Resource",
 *     description="Details of the completed withdrawal",
 *
 *     @OA\Property(property="withdraw_id", type="string", example="123456"),
 *     @OA\Property(property="amount", type="number", format="float", example=0.5),
 *     @OA\Property(property="currency_symbol", type="string", example="BTC"),
 *     @OA\Property(property="currency_chain", type="string", example="Bitcoin"),
 *     @OA\Property(property="transaction_hash", type="string", example="a1b2c3d4e5f6"),
 *     @OA\Property(property="wallet_address", type="string", example="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"),
 *     @OA\Property(property="status", type="string", example="COMPLETED"),
 *     @OA\Property(property="status_lang", type="string", example="Completed"),
 *     @OA\Property(property="explorer_address_url", type="string", example="https://blockchain.com/btc/address/1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"),
 *     @OA\Property(property="confirmed_at", type="string", format="date-time", example="2025-02-19 14:00:00")
 * )
 */
class CheckWithdrawalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'withdraw_id' => $this->resource->getWithdrawId(),
            'amount' => $this->resource->getAmount(),
            'currency_symbol' => $this->resource->getCurrencySymbol(),
            'currency_chain' => $this->resource->getCurrencyChain(),
            'transaction_hash' => $this->resource->getTransactionHash(),
            'wallet_address' => $this->resource->getWalletAddress(),
            'fee' => $this->resource->getTotalFee(),
            'status' => $this->resource->getStatus(),
            'status_lang' => __('enum.deposit-withdrawal.'.$this->resource->getStatus()->name),
            'explorer_address_url' => $this->resource->getExplorerAddressUrl(),
            'explorer_tx_url' => $this->resource->getExplorerTxUrl(),
            'confirmed_at' => $this->resource->getConfirmedAt(),
        ];
    }
}
