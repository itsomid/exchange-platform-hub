<?php

namespace App\Rules;

use App\Models\CurrencyChain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GeneralBlockchainAddress implements ValidationRule
{
    private ?string $chain;

    public function __construct(?string $chain = null)
    {
        $this->chain = $chain;
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidBlockchainAddress($value, $this->chain)) {
            $chainName = $this->getChainName($this->chain);
            $fail(__('validation.blockchain_address_invalid', ['chain' => $chainName]));
        }
    }

    /**
     * Validate blockchain address based on the chain type
     */
    private function isValidBlockchainAddress(string $address, ?string $chain): bool
    {
        if (!$chain) {
            // Fallback to general validation if no chain specified
            return preg_match('/^[a-zA-Z0-9]{26,42}$/', $address);
        }

        return match (strtoupper($chain)) {
            'BTC' => $this->isValidBitcoinAddress($address),
            'ETH', 'ERC20', 'BSC', 'BEP20', 'MATIC' => $this->isValidEthereumAddress($address),
            'TRX', 'TRC20' => $this->isValidTronAddress($address),
            'DOGE' => $this->isValidDogecoinAddress($address),
            'LTC' => $this->isValidLitecoinAddress($address),
            'SOL' => $this->isValidSolanaAddress($address),
            'XLM' => $this->isValidStellarAddress($address),
            'ADA' => $this->isValidCardanoAddress($address),
            'DOT' => $this->isValidPolkadotAddress($address),
            'AVAX' => $this->isValidAvalancheAddress($address),
            'FTM' => $this->isValidFantomAddress($address),
            'COSMOS' => $this->isValidCosmosAddress($address),
            'TEZOS' => $this->isValidTezosAddress($address),
            default => preg_match('/^[a-zA-Z0-9]{26,42}$/', $address), // General fallback
        };
    }

    /**
     * Validate Bitcoin address (Legacy P2PKH, P2SH, Bech32)
     */
    private function isValidBitcoinAddress(string $address): bool
    {
        // Legacy addresses (P2PKH): start with 1, 26-35 characters
        // P2SH addresses: start with 3, 26-35 characters  
        // Bech32 addresses: start with bc1, 42-62 characters
        return preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $address) ||
               preg_match('/^bc1[a-z0-9]{39,59}$/', $address);
    }

    /**
     * Validate Ethereum address (also valid for BSC, Polygon)
     */
    private function isValidEthereumAddress(string $address): bool
    {
        // Ethereum addresses: 0x followed by 40 hexadecimal characters
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }

    /**
     * Validate TRON address
     */
    private function isValidTronAddress(string $address): bool
    {
        // TRON addresses: start with T, 34 characters, base58 encoded
        return preg_match('/^T[A-Za-z0-9]{33}$/', $address);
    }

    /**
     * Validate Dogecoin address
     */
    private function isValidDogecoinAddress(string $address): bool
    {
        // Dogecoin addresses: start with D, 34 characters
        return preg_match('/^D[5-9A-HJ-NP-U][1-9A-HJ-NP-Za-km-z]{32}$/', $address);
    }

    /**
     * Validate Litecoin address
     */
    private function isValidLitecoinAddress(string $address): bool
    {
        // Litecoin Legacy: start with L or M, 26-35 characters
        // Litecoin Bech32: start with ltc1
        return preg_match('/^[LM][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $address) ||
               preg_match('/^ltc1[a-z0-9]{39,59}$/', $address);
    }

    /**
     * Validate Solana address
     */
    private function isValidSolanaAddress(string $address): bool
    {
        // Solana addresses: 32-44 characters, base58 encoded
        return preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address);
    }

    /**
     * Validate Stellar address
     */
    private function isValidStellarAddress(string $address): bool
    {
        // Stellar addresses: start with G, 56 characters
        return preg_match('/^G[A-Z2-7]{55}$/', $address);
    }

    /**
     * Validate Cardano address
     */
    private function isValidCardanoAddress(string $address): bool
    {
        // Cardano addresses: start with addr1, 59-108 characters
        return preg_match('/^addr1[a-z0-9]{58,107}$/', $address) ||
               preg_match('/^[Dd][a-zA-Z0-9]{57}$/', $address); // Legacy Byron addresses
    }

    /**
     * Validate Polkadot address
     */
    private function isValidPolkadotAddress(string $address): bool
    {
        // Polkadot addresses: start with 1, 47-48 characters, base58 encoded
        return preg_match('/^1[a-km-zA-HJ-NP-Z1-9]{46,47}$/', $address);
    }

    /**
     * Validate Avalanche address
     */
    private function isValidAvalancheAddress(string $address): bool
    {
        // Avalanche C-Chain uses Ethereum format
        // X-Chain and P-Chain use different formats
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) || // C-Chain
               preg_match('/^[XP]-avax1[a-z0-9]{38}$/', $address); // X-Chain or P-Chain
    }

    /**
     * Validate Fantom address
     */
    private function isValidFantomAddress(string $address): bool
    {
        // Fantom uses Ethereum-compatible addresses
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }

    /**
     * Validate Cosmos address
     */
    private function isValidCosmosAddress(string $address): bool
    {
        // Cosmos addresses: start with cosmos1, 39-59 characters
        return preg_match('/^cosmos1[a-z0-9]{38,58}$/', $address);
    }

    /**
     * Validate Tezos address
     */
    private function isValidTezosAddress(string $address): bool
    {
        // Tezos addresses: start with tz1, tz2, tz3, or KT1, 36 characters
        return preg_match('/^(tz1|tz2|tz3|KT1)[a-zA-Z0-9]{33}$/', $address);
    }

    /**
     * Get chain name from CurrencyChain model
     */
    private function getChainName(?string $chain): ?string
    {
        if (!$chain) {
            return null;
        }

        $currencyChain = CurrencyChain::where('chain', strtoupper($chain))->first();
        return $currencyChain ? $currencyChain->chain_name : $chain;
    }
}
