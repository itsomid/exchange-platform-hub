<?php

namespace App\Enums;

enum CurrencyChainEnum: string
{
    case BTC = 'BTC';         // Bitcoin blockchain

    case ETH = 'ETH';         // Ethereum Mainnet
    case TRX = 'TRX';         //Tron Mainnet
    case ERC20 = 'ERC20';     // Ethereum ERC-20 token standard
    case BEP20 = 'BEP20';     // Binance Smart Chain (BSC) BEP-20 token standard
    case TRC20 = 'TRC20';     // TRON TRC-20 token standard
    case BSC = 'BSC';         // Binance Smart Chain (Native chain)
    case DOGE = 'DOGE';       // Dogecoin blockchain
    case SOL = 'SOL';         // Solana blockchain
    case POLYGON = 'MATIC';   // Polygon (previously Matic Network)
    case LTC = 'LTC';         // Litecoin blockchain
    case AVAX = 'AVAX';       // Avalanche blockchain
    case FTM = 'FTM';         // Fantom blockchain
    case XLM = 'XLM';         // Stellar blockchain
    case ADA = 'ADA';         // Cardano blockchain
    case DOT = 'DOT';         // Polkadot blockchain
    case MITH = 'MITH';       // MITH (Mithril) blockchain
    case COSMOS = 'COSMOS';   // Cosmos blockchain
    case TEZOS = 'TEZOS';     // Tezos blockchain
}
