<?php

namespace App\Models;

use App\Enums\StockContractStatusEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockContract extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    public $filterNameSpace = 'App\Filters\StockContractFilter';

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

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'stock_contract_id');
    }

    public static function generateContractNumber()
    {
        $datePart = now()->format('Ymd');
        $countToday = self::whereDate('created_at', now()->toDateString())->count() + 1;
        $serial = str_pad($countToday, 4, '0', STR_PAD_LEFT);

        return "SH-{$datePart}-{$serial}";
    }
}
