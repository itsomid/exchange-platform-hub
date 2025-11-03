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
        return $this->belongsTo(SpotOrder::class, 'spot_order_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    // Helper methods
    public function getTypeLabel(): string
    {
        return $this->type->label();
    }

    public function getTypeColor(): string
    {
        return $this->type->color();
    }

    public function getRelatedEntityName(): ?string
    {
        switch ($this->type) {
            case LockedBalanceTypeEnum::WITHDRAWAL:
                return $this->withdrawal ? "برداشت #{$this->withdrawal->id}" : null;
            case LockedBalanceTypeEnum::SPOT:
                return $this->spot ? "سفارش اسپات #{$this->spot->id}" : null;
            case LockedBalanceTypeEnum::ADMIN:
                return $this->admin ? "ادمین: {$this->admin->fullname()}" : null;
            default:
                return null;
        }
    }

    public function getRelatedEntityUrl(): ?string
    {
        switch ($this->type) {
            case LockedBalanceTypeEnum::WITHDRAWAL:
                return $this->withdrawal ? route('admin.withdrawal.index', $this->withdrawal->id) : null;
            case LockedBalanceTypeEnum::SPOT:
                return $this->spot ? route('admin.spot_orders.index', $this->spot->id) : null;
            case LockedBalanceTypeEnum::ADMIN:
                return $this->admin ? route('admin.admin.index', $this->admin->id) : null;
            default:
                return null;
        }
    }
}
