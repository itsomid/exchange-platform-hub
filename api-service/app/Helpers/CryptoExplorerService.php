<?php

namespace App\Helpers;

class CryptoExplorerService
{
    protected static array $explorers = [
        'DOGE' => 'https://blockchair.com/dogecoin/address/',
        'BTC' => 'https://www.blockchain.com/btc/address/',
        'ERC20' => 'https://etherscan.io/address/',
        'TRC20' => 'https://tronscan.org/#/address/',
        'BSC' => 'https://bscscan.com/address/',
        'POLYGON' => 'https://polygonscan.com/address/',
        'ARBITRUM' => 'https://arbiscan.io/address/',
        'OPTIMISM' => 'https://optimistic.etherscan.io/address/',
        'AVAX' => 'https://snowtrace.io/address/',
        'AVALANCHE' => 'https://snowtrace.io/address/',
        'SONIC' => 'https://sonicscan.org/address/',
        'LTC' => 'https://blockchair.com/litecoin/address/',
    ];

    public static function getExplorerUrl(string $network, string $address): ?string
    {
        $key = strtoupper($network);

        return self::$explorers[$key] ?? null ? self::$explorers[$key].$address : null;
    }
}
