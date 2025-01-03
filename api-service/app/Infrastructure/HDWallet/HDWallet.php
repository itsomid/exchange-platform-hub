<?php

namespace App\Infrastructure\HDWallet;

class HDWallet
{
    public static function getBaseUrl(): string
    {
        return config('hd-wallet.base_url');
    }
}
