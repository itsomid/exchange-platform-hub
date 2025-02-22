<?php

namespace App\Models;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OTCRefExchangeWithdrawal extends Model
{
    protected $table = 'otc_ref_exchange_withdrawals';

    protected $fillable = [
        'currency_id',
        'transaction_id',
        'exchange_assets_withdrawal_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OTCRefExchangeWithdrawalStatusEnum::class,
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function exchangeAssetWithdrawal(): BelongsTo
    {
        return $this->belongsTo(ExchangeAssetsWithdrawal::class);
    }
}
