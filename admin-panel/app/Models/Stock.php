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
    ];

    public function contracts()
    {
        return $this->hasMany(StockContract::class);
    }
} 