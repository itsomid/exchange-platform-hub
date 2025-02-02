<?php

namespace App\Infrastructure\HDWallet;

class DepositApiRoutes
{
    const array ROUTES = [
        'bnb.BINANCE' => '/api/v1/wallet/deposits/bnb/{user_address}/all',
        'usdt.BINANCE' => '/api/v1/wallet/deposits/usdt/{user_address}/all',
        'doge.DOGE' => '/api/v1/wallet/deposits/doge/{user_address}',
        'btc.BITCOIN' => '/api/v1/wallet/deposits/btc/{user_address}/all',
        'usdt.TRON' => '/api/v1/wallet/deposits/tron/usdt/{user_address}/all',
        'trx.TRON' => '/api/v1/wallet/deposits/trx/{user_address}/all',
        'eth.ETHEREUM' => '/api/v1/wallet/deposits/eth/{user_address}/all',
        'usdt.ETHEREUM' => '/api/v1/wallet/deposits/eth/usdt/{user_address}/all',
    ];

    public static function get(string $currencySymbol, string $blockchain, string $walletAddress): string
    {
        $route = self::ROUTES[$currencySymbol.'.'.$blockchain];

        return str_replace('{user_address}', $walletAddress, $route);
    }
}
