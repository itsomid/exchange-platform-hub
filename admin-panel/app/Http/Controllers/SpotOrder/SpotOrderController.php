<?php

namespace App\Http\Controllers\SpotOrder;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\SpotOrder;
use App\Models\User;
use App\Models\TradingCommission;
use App\Models\SpotTrade;
use Illuminate\Http\Request;

class SpotOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SpotOrder::with([
            'user',
            'market.baseCurrency',
            'market.quoteCurrency',
            'makerTrades',
            'takerTrades',
            'makerTrades.commission',
            'takerTrades.commission'
        ]);

        // Filter by order type if provided
        if ($request->filled('type') && $request->type != ' ') {
            $query->where('type', $request->type);
        }

        // Filter by user if provided
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        // Sort by ID
        if ($request->filled('sortById')) {
            $query->orderBy('id', $request->sortById);
        } else {
            $query->orderBy('id', 'asc');
        }

        // Sort by quantity
        if ($request->filled('sortByQuantity')) {
            $query->orderBy('quantity', $request->sortByQuantity);
        }

        // Sort by creation date
        if ($request->filled('sortByCreatedAt')) {
            $query->orderBy('created_at', $request->sortByCreatedAt);
        }

        $spotOrders = $query->get();

        // Calculate commission values for each order's trades
        foreach ($spotOrders as $order) {
            $order->total_maker_commission_value = 0;
            $order->total_taker_commission_value = 0;
            $order->total_commission_value = 0;

            // Calculate for maker trades
            foreach ($order->makerTrades as $trade) {
                if ($trade->commission) {
                    $commissionValues = $this->calculateCommissionValues($trade, $trade->commission);
                    $trade->maker_commission_value = $commissionValues['maker_commission_value'];
                    $trade->taker_commission_value = $commissionValues['taker_commission_value'];
                    $trade->total_commission_value = $commissionValues['total_commission_value'];
                    $order->total_maker_commission_value = bcadd($order->total_maker_commission_value, $trade->maker_commission_value, 8);
                    $order->total_taker_commission_value = bcadd($order->total_taker_commission_value, $trade->taker_commission_value, 8);
                    $order->total_commission_value = bcadd($order->total_commission_value, $trade->maker_commission_value, 8);
                }
            }

            // Calculate for taker trades
            foreach ($order->takerTrades as $trade) {
                if ($trade->commission) {
                    $commissionValues = $this->calculateCommissionValues($trade, $trade->commission);
                    $trade->maker_commission_value = $commissionValues['maker_commission_value'];
                    $trade->taker_commission_value = $commissionValues['taker_commission_value'];
                    $trade->total_commission_value = $commissionValues['total_commission_value'];

                    $order->total_maker_commission_value = bcadd($order->total_maker_commission_value, $trade->maker_commission_value, 8);
                    $order->total_taker_commission_value = bcadd($order->total_taker_commission_value, $trade->taker_commission_value, 8);
                    $order->total_commission_value = bcadd($order->total_commission_value, $trade->taker_commission_value, 8);
                }
            }
        }
//        return $spotOrders;
        return view('dashboard.spot_order.index', [
            'spotOrders' => $spotOrders
        ]);
    }

    public function calculateCommissionValues(SpotTrade $trade, TradingCommission $commission) {
        // If maker is buyer, their commission is in base currency
        if ($trade->makerSide === 'buy') {
            // Convert maker commission (in base currency) to USDT
            $makerCommissionValue = bcmul($commission->maker_commission_amount, $trade->price, 8);
            // Taker commission is already in USDT
            $takerCommissionValue = $commission->taker_commission_amount;
        } else {
            // If maker is seller, their commission is in USDT
            $makerCommissionValue = $commission->maker_commission_amount;
            // Convert taker commission (in base currency) to USDT
            $takerCommissionValue = bcmul($commission->taker_commission_amount, $trade->price, 8);
        }

        // Total commission value in USDT
        $totalCommissionValue = bcadd($makerCommissionValue, $takerCommissionValue, 8);

        return [
            'maker_commission_value' => $makerCommissionValue,
            'taker_commission_value' => $takerCommissionValue,
            'total_commission_value' => $totalCommissionValue
        ];
    }
}
