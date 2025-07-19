<?php

namespace App\Enums;

enum CurrencyChainEnum: string
{
    case BTC = 'BTC';         // Bitcoin blockchain
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

    public function chain_name(): string
    {
        return match ($this) {
            self::BTC => 'Bitcoin',
            self::ERC20 => 'Ethereum (ERC20)',
            self::BEP20 => 'BSC (BEP20)',
            self::TRC20 => 'TRON (TRC20)',
            self::BSC => 'BSC (BEP20)',
            self::DOGE => 'Dogecoin',
            self::SOL => 'Solana',
            self::POLYGON => 'Polygon',
            self::LTC => 'Litecoin',
            self::AVAX => 'Avalanche',
            self::FTM => 'Fantom',
            self::XLM => 'Stellar',
            self::ADA => 'Cardano',
            self::DOT => 'Polkadot',
            self::MITH => 'MITH (Mithril)',
            self::COSMOS => 'Cosmos',
            self::TEZOS => 'Tezos',
        };
    }

    public function blockchain_name(): string
    {
        return match ($this) {
            self::BTC => 'BITCOIN',
            self::ERC20 => 'ETHEREUM',
            self::BEP20 => 'BSC',
            self::TRC20 => 'TRON',
            self::DOGE => 'DOGECOIN',
            self::SOL => 'SOLANA',
            self::POLYGON => 'POLYGON',
            self::LTC => 'LITECOIN',
            self::AVAX => 'AVALANCHE',
            self::FTM => 'FANTOM',
            self::XLM => 'STELLAR',
        };
    }
}
