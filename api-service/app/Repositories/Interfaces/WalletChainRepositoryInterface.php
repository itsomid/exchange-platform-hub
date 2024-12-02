<?php

namespace App\Repositories\Interfaces;

use App\Models\WalletChain;

interface WalletChainRepositoryInterface
{
    public function createOrGetChain(int $walletId, string $currencyChain): WalletChain;

    public function savePublicKey(int $walletChainId, string $publicKey): void;
}
