<?php

namespace App\Models;

use App\Enums\CurrencyBlockChainNameEnum;
use App\Enums\CurrencyChainEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property CurrencyBlockChainNameEnum $blockchain_name
 *
 * @method string totalWithdrawalFee(string $getCurrencyChain)
 */
class CurrencyChain extends Model
{
    protected $casts = [
        'network_fee' => 'float',
        'exchange_withdrawal_fee' => 'float',
        'chain' => CurrencyChainEnum::class,
        'deposit_enabled' => 'boolean',
        'withdraw_enabled' => 'boolean',
        'blockchain_name' => CurrencyBlockChainNameEnum::class,
        'is_base_coin' => 'boolean',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeTotalWithdrawalFee($query, $currencyChain)
    {
        return $query->where('chain', $currencyChain)
            ->sum(DB::raw('network_fee + exchange_withdrawal_fee'));
    }

    /**
     * Generate contract address URL for blockchain explorers
     * 
     * @return string|null
     */
    public function getContractAddressUrl(): ?string
    {
        if (!$this->contract_address) {
            return null;
        }

        // Map blockchain names to their explorer URLs
        $explorerUrls = [
            'BSC' => 'https://bscscan.com/token/',
            'ETHEREUM' => 'https://etherscan.io/token/',
            'POLYGON' => 'https://polygonscan.com/token/',
            'TRON' => 'https://tronscan.org/#/token20/',
            'ARBITRUM' => 'https://arbiscan.io/token/',
            'OPTIMISM' => 'https://optimistic.etherscan.io/token/',
            'AVALANCHE' => 'https://snowtrace.io/token/',
        ];

        $blockchainName = $this->blockchain_name?->value;
        
        if (!$blockchainName || !isset($explorerUrls[$blockchainName])) {
            return null;
        }

        return $explorerUrls[$blockchainName] . $this->contract_address;
    }
}
