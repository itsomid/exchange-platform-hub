<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalApiConfig extends Model
{
    protected $fillable = ['name', 'key', 'status'];
}
