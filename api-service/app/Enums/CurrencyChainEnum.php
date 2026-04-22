<?php

namespace App\Enums;

enum CurrencyChainEnum: string
{
    case BTC = 'BTC';         // Bitcoin blockchain
    case ETH = 'ETH';         // Ethereum Mainnet
    case ERC20 = 'ERC20';     // Ethereum ERC-20 token standard
    case BEP20 = 'BEP20';     // Binance Smart Chain (BSC) BEP-20 token standard
    case TRC20 = 'TRC20';     // TRON TRC-20 token standard
    case BSC = 'BSC';         // Binance Smart Chain (Native chain)
    case DOGE = 'DOGE';       // Dogecoin blockchain
    case POLYGON = 'POLYGON'; // Polygon blockchain  
    case ARBITRUM = 'ARBITRUM'; // Arbitrum One (L2)
    case OPTIMISM = 'OPTIMISM'; // Optimism (L2)
    case AVALANCHE = 'AVALANCHE';       // Avalanche blockchain
    case SONIC = 'SONIC';     // Sonic blockchain
}
