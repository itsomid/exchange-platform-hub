<?php

namespace App\Http\Controllers\OTCOrder;

use App\Http\Controllers\Controller;
use App\Models\OTCOrder;
use Illuminate\Http\Request;

class OTCOrderController extends Controller
{
    public function index()
    {
         $otcOrders = OTCOrder::with(['market','transactions'])->get();

        return view('dashboard.otc_order.index', [
            'otcOrders' => $otcOrders
        ]);
    }
}
