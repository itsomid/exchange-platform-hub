<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SweeperTransactionLog extends Model
{
    protected $fillable = [
        'sweeper_id',
        'tx_hash',
        'network',
        'symbol',
        'wallet_id',
        'address_index',
        'type',
        'coin_type',
        'from_address',
        'to_address',
        'derivation_path',
        'amount',
        'amount_in_wei',
        'amount_in_satoshi',
        'fee',
        'fee_in_wei',
        'fee_in_satoshi',
        'gas_used',
        'gas_price',
        'status',
        'confirmations',
        'required_confirmations',
        'block_number',
        'block_hash',
        'broadcast_at',
        'confirmed_at',
        'sweeper_created_at',
        'sweeper_updated_at',
        'synced_at',
        'error',
        'metadata',
        'raw_payload',
        'withdrawal_transaction_id',
        'fee_transaction_id',
        'transactions_created_at',
    ];

    protected $casts = [
        'address_index' => 'integer',
        'confirmations' => 'integer',
        'required_confirmations' => 'integer',
        'block_number' => 'integer',
        'broadcast_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'sweeper_created_at' => 'datetime',
        'sweeper_updated_at' => 'datetime',
        'synced_at' => 'datetime',
        'transactions_created_at' => 'datetime',
        'metadata' => 'array',
        'raw_payload' => 'array',
    ];

    public function withdrawalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'withdrawal_transaction_id');
    }

    public function feeTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'fee_transaction_id');
    }

    public function hasAccountingTransactions(): bool
    {
        return $this->withdrawal_transaction_id && $this->fee_transaction_id;
    }
}
