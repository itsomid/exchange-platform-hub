<?php

namespace App\Http\Controllers\OTCOrder;

use App\Enums\OTCOrderTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\OTCOrder;
use Illuminate\Http\Request;

class OTCOrderController extends Controller
{
    public function index()
    {
        $today = now()->toDateString(); // Get today's date
        $otcOrders = OTCOrder::filterBy(request()->all())->with(['market', 'transactions'])
            ->orderBy('id', request()->input('sortById', 'desc'))
            ->paginate(50);

        $todayOrderCount =  OTCOrder::whereDate('created_at', $today)->count();
        $totalSellOrderCount =  OTCOrder::whereType(OTCOrderTypeEnum::SELL)->count();
        $totalBuyOrderCount =  OTCOrder::whereType(OTCOrderTypeEnum::BUY)->count();

        $totalOrdersValue = OTCOrder::with('market')
            ->get()
            ->sum('total_value');

        $totalTodayOrdersValue = OTCOrder::with('market')
            ->whereDate('created_at', $today)
            ->get()
            ->sum('total_value');


        $topUsers = OTCOrder::with(['market', 'user'])
            ->whereDate('created_at', $today)
            ->get()
            ->groupBy('user_id')
            ->map(function ($orders, $userId) {
                $totalOrders = $orders->sum('total_value');
                return [
                    'user' => $orders->first()->user,
                    'totalOrders' => $totalOrders,
                ];
            })
            ->sortByDesc('totalDeposit')
            ->take(5);


        return view('dashboard.otc_order.index', [
            'otcOrders' => $otcOrders,
            'todayOrderCount' => $todayOrderCount,
            'topUsers' => $topUsers,
            'totalTodayOrdersValue' => $totalTodayOrdersValue,
            'totalOrdersValue' => $totalOrdersValue,
            'totalSellOrderCount' => $totalSellOrderCount,
            'totalBuyOrderCount' => $totalBuyOrderCount,
        ]);
    }
}
