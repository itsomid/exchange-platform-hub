<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockedBalanceDetail extends Model
{
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
