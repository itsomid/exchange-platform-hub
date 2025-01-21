<?php

namespace App\Http\Controllers\OTCOrder;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\OTCOrder;
use Illuminate\Http\Request;

class OTCOrderController extends Controller
{
    public function index()
    {
        $today = now()->toDateString(); // Get today's date
        $otcOrders = OTCOrder::with(['market', 'transactions'])->filterBy(request()->all())->paginate(50);

        $totalOrdersValue = OTCOrder::with('market')
            ->whereDate('created_at', $today)// Assuming `currency` has the price
            ->get()
            ->sum(function ($order) {
                return $order->quantity * $order->price; // Multiply amount by coin price
            });


         $topUsers = OTCOrder::with(['market', 'user'])
             ->whereDate('created_at', $today)
            ->get()
            ->groupBy('user_id')
            ->map(function ($orders, $userId) {
                $totalOrders = $orders->sum(function ($order) {
                    return $order->quantity * $order->price;
                });
                return [
                    'user' => $orders->first()->user,
                    'totalOrders' => $totalOrders,
                ];
            })
            ->sortByDesc('totalDeposit')
            ->take(5);


        return view('dashboard.otc_order.index', [
            'otcOrders' => $otcOrders,
            'topUsers' => $topUsers,
            'totalOrdersValue' => $totalOrdersValue
        ]);
    }
}
