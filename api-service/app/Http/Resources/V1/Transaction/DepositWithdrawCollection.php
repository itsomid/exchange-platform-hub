<?php

namespace App\Http\Resources\V1\Transaction;

use App\Services\Transaction\DTO\GetAllDepositWithdrawResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="DepositWithdrawResource",
 *     type="object",
 *     title="Deposit and Withdraw Resource",
 *     description="A resource representing a single deposit or withdrawal transaction.",
 *
 *     @OA\Property(property="id", type="number", format="int", description="Transaction id", example=1),
 *     @OA\Property(property="amount", type="number", format="float", description="Transaction amount", example=100.5),
 *     @OA\Property(property="type", type="string", enum={"deposit", "withdrawal"}, description="Transaction type (deposit or withdrawal)", example="deposit"),
 *     @OA\Property(property="currency_symbol", type="string", description="Currency symbol", example="BTC"),
 *     @OA\Property(property="currency_chain", type="string", description="Currency chain", example="BTC"),
 *     @OA\Property(property="wallet_address", type="string", description="Wallet address", example="0xDE746Fb03a114674449d54ca5C90d34BBEB500ab"),
 *     @OA\Property(property="transaction_hash", type="string", description="Transaction hashed", example="0xbeda06f753e2e507bd396049ab57bc4bd6685183"),
 *     @OA\Property(property="confirmed_at", type="string", format="date-time", description="Transaction confirmed time", example="2024-12-21T14:30:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Transaction date and time", example="2024-12-21T14:30:00Z"),
 *     @OA\Property(property="status", enum={"success", "failed", "pending"}, type="string", description="Transaction status", example="completed")
 * )
 */
class DepositWithdrawCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn (GetAllDepositWithdrawResponseDTO $responseDTO) => [
            'id' => $responseDTO->getId(),
            'amount' => $responseDTO->getAmount(),
            'currency_symbol' => $responseDTO->getCurrencySymbol(),
            'currency_chain' => $responseDTO->getCurrencyChain(),
            'transaction_hash' => $responseDTO->getTransactionHashed(),
            'wallet_address' => $responseDTO->getAddress(),
            'type' => $responseDTO->getType()->value,
            'type_lang' => __('enum.transaction-type.'.$responseDTO->getType()->name),
            'status' => $responseDTO->getStatus(),
            'status_lang' => __('enum.deposit-withdrawal.'.$responseDTO->getStatus()),
            'explorer_address_url' => $responseDTO->getExplorerAddressUrl(),
            'explorer_tx_url' => $responseDTO->getExplorerTxUrl(),
            'confirmed_at' => $responseDTO->getConfirmedAt(),
            'created_at' => $responseDTO->getCreatedAt(),
        ])->toArray();
    }
}
