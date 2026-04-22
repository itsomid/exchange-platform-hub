<?php

namespace App\Enums;

enum CurrencyChainEnum: string
{
    case BTC = 'BTC';         // Bitcoin blockchain
    case ERC20 = 'ERC20';     // Ethereum ERC-20 token standard
    case TRC20 = 'TRC20';     // TRON TRC-20 token standard
    case BSC = 'BSC';         // Binance Smart Chain (Native chain)
    case DOGE = 'DOGE';       // Dogecoin blockchain
    case POLYGON = 'POLYGON';   // Polygon
    case ARBITRUM = 'ARBITRUM'; // Arbitrum One (L2) 
    case OPTIMISM = 'OPTIMISM'; // Optimism (L2)
    case AVALANCHE = 'AVALANCHE';       // Avalanche blockchain
    case LTC = 'LTC';         // Litecoin blockchain
    case SONIC = 'SONIC';     // Sonic blockchain
    public function chain_name(): string
    {
        return match ($this) {
            self::BTC => 'Bitcoin',
            self::ERC20 => 'Ethereum (ERC20)',
            self::TRC20 => 'TRON (TRC20)',
            self::BSC => 'BSC (BEP20)',
            self::DOGE => 'Dogecoin',
            self::POLYGON => 'Polygon',
            self::ARBITRUM => 'Arbitrum One',
            self::OPTIMISM => 'Optimism',
            self::LTC => 'Litecoin',
            self::AVALANCHE => 'Avalanche',
            self::SONIC => 'Sonic',
         
        };
    }

    public function blockchain_name(): string
    {
        return match ($this) {
            self::BTC => 'BITCOIN',
            self::ERC20 => 'ETHEREUM',
            self::BSC => 'BINANCE',
            self::TRC20 => 'TRON',
            self::DOGE => 'DOGECOIN',
            self::POLYGON => 'POLYGON',
            self::LTC => 'LITECOIN',
            self::AVALANCHE => 'AVALANCHE',
            self::ARBITRUM => 'ARBITRUM',
            self::OPTIMISM => 'OPTIMISM',
            self::SONIC => 'SONIC',
        };
    }
}
