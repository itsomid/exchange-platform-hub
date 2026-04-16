<?php

namespace App\Infrastructure\HDWalletNew;

/**
 * BlockchainNetworkMapper
 * 
 * Maps between old HD Wallet blockchain names and new HD Wallet network names
 */
class BlockchainNetworkMapper
{
    /**
     * Map old blockchain name to new network name
     * 
     * Old System: BINANCE, DOGE, BITCOIN, TRON, ETHEREUM
     * New System: bnb, dogecoin, bitcoin, tron, ethereum
     */
    private const OLD_TO_NEW = [
        'BINANCE' => 'bnb',
        'DOGE' => 'dogecoin',
        'BITCOIN' => 'bitcoin',
        'TRON' => 'tron',
        'ETHEREUM' => 'ethereum',
        'POLYGON' => 'polygon',
        'ARBITRUM' => 'arbitrum',
        'OPTIMISM' => 'optimism',
        'AVALANCHE' => 'avalanche'
    ];

    /**
     * Map new network name to old blockchain name
     */
    private const NEW_TO_OLD = [
        'bnb' => 'BINANCE',
        'dogecoin' => 'DOGE',
        'bitcoin' => 'BITCOIN',
        'tron' => 'TRON',
        'ethereum' => 'ETHEREUM',
        'polygon' => 'POLYGON',
        'arbitrum' => 'ARBITRUM',
        'optimism' => 'OPTIMISM',
        'avalanche' => 'AVALANCHE'
    ];

    /**
     * Convert old blockchain name to new network name
     * 
     * @param string $oldBlockchainName Old blockchain name (e.g., 'BINANCE', 'ETHEREUM')
     * @return string New network name (e.g., 'bnb', 'ethereum')
     */
    public static function toNewNetwork(string $oldBlockchainName): string
    {
        $upperName = strtoupper($oldBlockchainName);
        
        return self::OLD_TO_NEW[$upperName] ?? strtolower($oldBlockchainName);
    }

    /**
     * Convert new network name to old blockchain name
     * 
     * @param string $newNetworkName New network name (e.g., 'bnb', 'ethereum')
     * @return string Old blockchain name (e.g., 'BINANCE', 'ETHEREUM')
     */
    public static function toOldBlockchain(string $newNetworkName): string
    {
        $lowerName = strtolower($newNetworkName);
        
        return self::NEW_TO_OLD[$lowerName] ?? strtoupper($newNetworkName);
    }

    /**
     * Check if blockchain/network is supported
     * 
     * @param string $name Blockchain or network name
     * @param bool $isOldSystem Whether to check against old system names
     * @return bool
     */
    public static function isSupported(string $name, bool $isOldSystem = true): bool
    {
        if ($isOldSystem) {
            return isset(self::OLD_TO_NEW[strtoupper($name)]);
        }
        
        return isset(self::NEW_TO_OLD[strtolower($name)]);
    }

    /**
     * Get all supported old blockchain names
     * 
     * @return array
     */
    public static function getOldBlockchainNames(): array
    {
        return array_keys(self::OLD_TO_NEW);
    }

    /**
     * Get all supported new network names
     * 
     * @return array
     */
    public static function getNewNetworkNames(): array
    {
        return array_keys(self::NEW_TO_OLD);
    }
}
