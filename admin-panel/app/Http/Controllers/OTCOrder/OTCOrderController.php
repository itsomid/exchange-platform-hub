<?php

namespace App\Http\Controllers\OTCOrder;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Enums\RefExchangeSellStatusEnum;
use App\Exports\OTCOrderExport;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\OTCOrder;
use App\Services\Exchanges\ExchangeService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OTCOrderController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $query = OTCOrder::filterBy(request()->all())->with(['market', 'transactions', 'user']);

        if (request()->filled('sortByCreatedAt')) {
            $query->reorder('created_at', request()->input('sortByCreatedAt'));
        } elseif (request()->filled('sortByQuantity')) {
            $query->reorder('quantity', request()->input('sortByQuantity'));
        } elseif (request()->filled('sortByTotalValue')) {
            $query->reorder(\Illuminate\Support\Facades\DB::raw('price * quantity'), request()->input('sortByTotalValue'));
        } else {
            $query->orderBy('id', request()->input('sortById', 'desc'));
        }

        $otcOrders = $query->paginate(50);

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


        $markets = Market::where('is_active', true)->get(['id', 'base_currency', 'quote_currency']);

        return view('dashboard.otc_order.index', [
            'otcOrders' => $otcOrders,
            'markets' => $markets,
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
        $filename = 'otc_orders_' . now()->format('Y-m-d_H-i-s');

        $otcOrders = OTCOrder::orderBy('id')->filterBy($request->all())->get();

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
                DateFormatter::convertToPersianDate($order->created_at,'%Y/%m/%d H:i:s'),
                $order->status->label(),
                $order->exchange ? $order->exchange->slug : 'داخلی',
                $order->ref_exchange_description
            ];
        });

        return Excel::download(new OTCOrderExport($otcOrders), $filename . '.xlsx');
    }

    /**
     * Trigger sell in reference exchange for an OTC order
     */
    public function triggerRefExchangeSell(int $otcOrderId, ExchangeService $exchangeService)
    {
        $otcOrder = OTCOrder::findOrFail($otcOrderId);

        // Validate the order
        if ($otcOrder->type !== OTCOrderTypeEnum::SELL) {
            return response()->json([
                'success' => false,
                'message' => 'این عملیات فقط برای سفارشات فروش قابل انجام است.',
            ], 400);
        }

        if ($otcOrder->status !== OTCOrderStatusEnum::SUCCESS) {
            return response()->json([
                'success' => false,
                'message' => 'فقط سفارشات موفق قابل تکمیل در صرافی مرجع هستند.',
            ], 400);
        }

        if ($otcOrder->ref_exchange_sell_status !== RefExchangeSellStatusEnum::PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'این سفارش در وضعیت مناسب برای فروش در صرافی مرجع نیست.',
            ], 400);
        }

        $result = $exchangeService->triggerRefExchangeSell($otcOrderId);

        return response()->json([
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
        ], $result->isSuccess() ? 200 : 400);
    }

    /**
     * Reset the ref exchange sell status to pending (for retry)
     */
    public function resetRefExchangeSellStatus(int $otcOrderId)
    {
        $otcOrder = OTCOrder::findOrFail($otcOrderId);

        if ($otcOrder->ref_exchange_sell_status !== RefExchangeSellStatusEnum::FAILED) {
            return response()->json([
                'success' => false,
                'message' => 'فقط سفارشات ناموفق قابل ریست هستند.',
            ], 400);
        }

        $otcOrder->update([
            'ref_exchange_sell_status' => RefExchangeSellStatusEnum::PENDING,
            'ref_exchange_description' => null, // Clear the error description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'وضعیت سفارش به "در انتظار" تغییر یافت. می‌توانید مجدداً تلاش کنید.',
        ]);
    }
}
