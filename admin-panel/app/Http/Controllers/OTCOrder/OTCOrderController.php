<?php

namespace App\Http\Controllers\OTCOrder;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Exports\OTCOrderExport;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Models\OTCOrder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OTCOrderController extends Controller
{
    public function index()
    {
        $today = now()->toDateString(); // Get today's date
        $otcOrders = OTCOrder::filterBy(request()->all())->with(['market', 'transactions'])
            ->orderBy('id', request()->input('sortById', 'desc'))
            ->paginate(50);

        $todayOrderCount =  OTCOrder::whereDate('created_at', $today)
            ->whereStatus(OTCOrderStatusEnum::SUCCESS)->count();

        $totalSellOrderCount =  OTCOrder::whereType(OTCOrderTypeEnum::SELL)
            ->whereStatus(OTCOrderStatusEnum::SUCCESS)->count();
        $totalBuyOrderCount =  OTCOrder::whereType(OTCOrderTypeEnum::BUY)
            ->whereStatus(OTCOrderStatusEnum::SUCCESS)->count();

        $totalOrdersValue = OTCOrder::with('market')
            ->whereStatus(OTCOrderStatusEnum::SUCCESS)
            ->get()
            ->sum('total_value');

        $totalTodayOrdersValue = OTCOrder::with('market')
            ->whereStatus(OTCOrderStatusEnum::SUCCESS)
            ->whereDate('created_at', $today)
            ->get()
            ->sum('total_value');


        $topUsers = OTCOrder::with(['market', 'user'])
            ->whereDate('created_at', $today)
            ->whereStatus(OTCOrderStatusEnum::SUCCESS)
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

    public function excelExport(Request $request)
    {
        $from = $request->get('from_id');
        $to = $request->get('to_id');
        $filename = 'otc_orders_' . $from . '_' . $to;

        $OTCOrderQuery = OTCOrder::orderBy('id')->filterBy(request()->all());
        if ($request->get('from_id') && $request->get('to_id')) {
            $OTCOrderQuery->where('id', '>=', $request->from_id)
                ->where('id', '<=', $request->to_id);
        }
        $otcOrders = $OTCOrderQuery->get();

        $otcOrders = $otcOrders->map(function (OTCOrder $order) {
            if ($order->type === \App\Enums\OTCOrderTypeEnum::BUY){
                $userRecieved = formatNumberTrimZeros(bcsub($order->quantity ,  $order->fee,8));
            }else{
                $userRecieved = formatNumberTrimZeros(bcsub(bcmul($order->price , $order->quantity,5) ,  $order->fee,5));
            }

            return [
                $order->id,
                $order->market->name,
                $order->type->label(),
                $order->user->email,
                formatNumberTrimZeros($order->quantity),
                formatNumberTrimZeros($order->price),
                formatNumberTrimZeros($order->total_value),
                formatNumberTrimZeros($order->fee),
                $userRecieved,
                DateFormatter::convertToPersianDate($order->created_at,'H:i:s %Y/%m/%d'),
                $order->status->label(),
                $order->exchange ? $order->exchange->slug : 'داخلی',
                $order->ref_exchange_description
            ];
        });

        return Excel::download(new OTCOrderExport($otcOrders), $filename . '.xlsx');
    }
}
