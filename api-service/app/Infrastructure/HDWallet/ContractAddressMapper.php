<?php

namespace App\Infrastructure\HDWallet;

class ContractAddressMapper
{
    const array CONTRACT_ADDRESSES = [
        'ethereum' => [
            'USDT' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
            'AUDIO' => '0x18aAA7115705e8be94bfFEBDE57Af9BFc265B998',
            
            // Add more Ethereum tokens here
        ],
        'binance' => [
            'USDT' => '0x55d398326f99059fF775485246999027B3197955',
            // Add more BSC tokens here
        ],
        'tron' => [
            'USDT' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
            // Add more TRON tokens here
        ],
        // Add more networks here
    ];

    const array NETWORK_MAPPING = [
        'BINANCE' => 'binance',
        'ETHEREUM' => 'ethereum',
        'TRON' => 'tron',
        'BITCOIN' => 'bitcoin',
        'DOGE' => 'doge',
    ];

    const array NATIVE_TOKENS = [
        'ethereum' => 'ETH',
        'binance' => 'BNB',
        'tron' => 'TRX',
        'bitcoin' => 'BTC',
        'doge' => 'DOGE',
    ];

    public static function getContractAddress(string $network, string $tokenSymbol): ?string
    {
        $normalizedNetwork = strtolower($network);
        
        // Check if it's a native token
        if (isset(self::NATIVE_TOKENS[$normalizedNetwork]) && 
            self::NATIVE_TOKENS[$normalizedNetwork] === strtoupper($tokenSymbol)) {
            return null;
        }

        return self::CONTRACT_ADDRESSES[$normalizedNetwork][strtoupper($tokenSymbol)] ?? null;
    }

    public static function mapNetworkName(string $blockchainName): string
    {
        return self::NETWORK_MAPPING[strtoupper($blockchainName)] ?? strtolower($blockchainName);
    }

    public static function isNativeToken(string $network, string $tokenSymbol): bool
    {
        $normalizedNetwork = strtolower($network);
        return isset(self::NATIVE_TOKENS[$normalizedNetwork]) && 
               self::NATIVE_TOKENS[$normalizedNetwork] === strtoupper($tokenSymbol);
    }
}