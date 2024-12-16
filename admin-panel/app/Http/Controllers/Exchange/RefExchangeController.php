<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Exchange;

class RefExchangeController extends Controller
{
    public function index()
    {
        $exchanges = Exchange::query()->all();
    }
}
