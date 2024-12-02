<?php

namespace App\Repositories\Interfaces;

use App\Models\Wallet;
use App\Models\WalletChain;

interface WalletRepositoryInterface
{
    public function createOrGetWallet(string $symbol, int $userId): Wallet;
}
