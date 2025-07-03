<?php

namespace App\Models;

use App\Enums\StockContractStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockContract extends Model
{
    use HasFactory;

    protected $casts = [
        'contract_status' => StockContractStatusEnum::class,
    ];

    protected $fillable = [
        'user_id',
        'stock_id',
        'contract_number',
        'contract_file',
        'amount',
        'total_value',
        'contract_status',
        'cancellation_fee',
        'cancelled_at',
        'sold_at',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public static function generateContractNumber()
    {
        $datePart = now()->format('Ymd');
        $countToday = self::whereDate('created_at', now()->toDateString())->count() + 1;
        $serial = str_pad($countToday, 4, '0', STR_PAD_LEFT);

        return "SH-{$datePart}-{$serial}";
    }

    /**
     * Get the application base URL
     *
     * @return string
     */
    public static function getBaseUrl(): string
    {
        return config('bitexroom.contracts.base_url');
    }


    /**
     * Get the full contract file URL
     *
     * @return string|null
     */
    public function getContractFileUrlAttribute(): ?string
    {
        if (!$this->contract_file) {
            return null;
        }

        return self::getBaseUrl() . '/storage/contracts/stock/' . $this->contract_file;
    }
}
