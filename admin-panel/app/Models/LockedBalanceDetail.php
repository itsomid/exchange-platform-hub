<?php

namespace App\Models;

use App\Enums\LockedBalanceTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LockedBalanceDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'type',
        'withdrawal_id',
        'otc_order_id',
        'spot_order_id',
        'admin_id',
        'amount',
        'description'
    ];

    protected function casts(): array
    {
        return [
            'type' => LockedBalanceTypeEnum::class,
        ];
    }

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    public function otc(): BelongsTo
    {
        return $this->belongsTo(OTCOrder::class, 'otc_order_id');
    }

    public function spot(): BelongsTo
    {
        return $this->belongsTo(SpotOrder::class);
    }
}
