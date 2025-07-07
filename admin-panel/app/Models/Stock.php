<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'type',
        'cancellation_fee',
        'description',
        'status',
    ];

    protected $casts = [
        'type' => \App\Enums\StockTypeEnum::class,
        'status' => \App\Enums\StockStatusEnum::class,
        'cancellation_fee' => 'decimal:2',
    ];

    public function contracts()
    {
        return $this->hasMany(StockContract::class);
    }

    /**
     * Calculate the actual cancellation fee amount based on a given value
     */
    public function calculateCancellationFeeAmount($value)
    {
        return ($value * $this->cancellation_fee) / 100;
    }
} 