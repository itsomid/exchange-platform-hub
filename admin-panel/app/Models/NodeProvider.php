<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NodeProvider extends Model
{
    protected $fillable = [
        'name',
        'api_key',
        'base_url',
        'priority',
        'is_active'
    ];
}
