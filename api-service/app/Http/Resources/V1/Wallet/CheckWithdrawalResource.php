<?php

namespace App\Http\Resources\V1\Wallet;

use App\Helpers\CryptoExplorerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'status' => $this->resource->getStatus(),
            'status_lang' => __('enum.deposit-withdrawal.'.$this->resource->getStatus()->name),
            'explorer_address_url' => CryptoExplorerService::getExplorerUrl($this->resource->getCurrencyChain(), $this->resource->getWalletAddress()),
            'confirmed_at' => $this->resource->getConfirmedAt(),
        ];
    }
}
