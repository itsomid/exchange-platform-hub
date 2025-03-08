<?php

namespace App\Models;

use App\Enums\FinancialBlockReasonsEnum;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class UserFinancialBlock extends Model
{
    use Filterable, SoftDeletes;
    public $filterNameSpace = 'App\Filters\FinancialBlockFilters';
    protected $fillable = [
        'user_id',
        'action',
        'reason',
        'admin_id',
        'description',
        'restricted_until',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function isExpired()
    {
        return $this->restricted_until < Carbon::now();
    }
    public function setReasonAttribute($value)
    {
        if (!FinancialBlockReasonsEnum::tryFrom($value)) {
            throw new \InvalidArgumentException("Invalid reason provided: $value");
        }
        $this->attributes['reason'] = $value;
    }

}
