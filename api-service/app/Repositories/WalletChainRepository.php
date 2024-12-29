<?php

namespace App\Repositories;

use App\Models\WalletChain;
use App\Repositories\Interfaces\WalletChainRepositoryInterface;

class WalletChainRepository implements WalletChainRepositoryInterface
{
    public function createOrGetChain(int $walletId, string $currencyChain): WalletChain
    {
        return WalletChain::query()
            ->firstOrCreate(
                [
                    'wallet_id' => $walletId,
                    'currency_chain' => $currencyChain,
                ],
                [
                    'address' => null,
                ]
            );
    }

    public function savePublicKey(int $walletChainId, string $publicKey): void
    {
        WalletChain::query()
            ->where('wallet_id', $walletChainId)
            ->update(['address' => $publicKey]);
    }
}
