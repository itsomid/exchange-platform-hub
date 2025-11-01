<?php

namespace App\Http\Controllers\SpotOrder;


use App\Enums\SpotOrderSourceEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\SpotOrder;
use App\Models\TradingCommission;
use App\Models\SpotTrade;
use App\Models\Market;
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

        // Filter by market if provided
        if ($request->filled('market')) {
            $query->where('market_id', $request->market);
        }

        // Filter by order type if provided
        if ($request->filled('type') && $request->type != ' ') {
            $query->where('type', $request->type);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user if provided
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by quantity range
        if ($request->filled('quantity_min')) {
            $query->where('quantity', '>=', $request->quantity_min);
        }
        if ($request->filled('quantity_max')) {
            $query->where('quantity', '<=', $request->quantity_max);
        }

        // Filter by price range
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Filter by source - show USER orders and completed BOT orders
        $source = $request->input('source', 'user');
        if ($source === 'bot') {
            // Show only BOT orders
            $query->where('source', SpotOrderSourceEnum::BOT->value);
        } else {
            // Show USER orders + completed BOT orders
            $query->where(function ($q) {
                $q->where('source', SpotOrderSourceEnum::USER->value)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('source', SpotOrderSourceEnum::BOT->value)
                            ->whereIn('status', [
                                SpotOrderStatusEnum::COMPLETED->value,
                                SpotOrderStatusEnum::PARTIALLY_FILLED_CANCELED->value
                            ]);
                    });
            });
        }

        // Handle sorting - only apply one sort at a time
        $sortApplied = false;

        // Sort by ID
        if ($request->filled('sortById') && !$sortApplied) {
            $query->orderBy('id', $request->sortById);
            $sortApplied = true;
        }

        // Sort by quantity
        if ($request->filled('sortByQuantity') && !$sortApplied) {
            $query->orderBy('quantity', $request->sortByQuantity);
            $sortApplied = true;
        }

        // Sort by price
        if ($request->filled('sortByPrice') && !$sortApplied) {
            $query->orderBy('price', $request->sortByPrice);
            $sortApplied = true;
        }

        // Sort by filled quantity
        if ($request->filled('sortByFilledQuantity') && !$sortApplied) {
            $query->orderBy('filled_quantity', $request->sortByFilledQuantity);
            $sortApplied = true;
        }

        // Sort by creation date
        if ($request->filled('sortByCreatedAt') && !$sortApplied) {
            $query->orderBy('created_at', $request->sortByCreatedAt);
            $sortApplied = true;
        }

        // Default sorting if no sort parameter is provided
        if (!$sortApplied) {
            $query->orderBy('id', 'desc');
        }

        $spotOrders = $query->paginate(50);

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
                    //                    $trade->total_commission_value = $commissionValues['total_commission_value'];
                    $order->total_maker_commission_value = bcadd($order->total_maker_commission_value, $trade->maker_commission_value, 8);
                    $order->total_taker_commission_value = bcadd($order->total_taker_commission_value, $trade->taker_commission_value, 8);
                    $order->total_commission_value = bcadd($order->total_commission_value, $commissionValues['total_commission_value'], 8);
                }
            }

            // Calculate for taker trades
            foreach ($order->takerTrades as $trade) {
                if ($trade->commission) {
                    $commissionValues = $this->calculateCommissionValues($trade, $trade->commission);
                    $trade->maker_commission_value = $commissionValues['maker_commission_value'];
                    $trade->taker_commission_value = $commissionValues['taker_commission_value'];
                    //                    $trade->total_commission_value = $commissionValues['total_commission_value'];

                    $order->total_maker_commission_value = bcadd($order->total_maker_commission_value, $trade->maker_commission_value, 8);
                    $order->total_taker_commission_value = bcadd($order->total_taker_commission_value, $trade->taker_commission_value, 8);
                    $order->total_commission_value = bcadd($order->total_commission_value, $commissionValues['total_commission_value'], 8);
                }
            }
        }
        //        return $spotOrders;
        // Add these counts to your index method
        $userOrdersCount = SpotOrder::where('source', SpotOrderSourceEnum::USER->value)->count();
        $botOrdersCount = SpotOrder::where('source', SpotOrderSourceEnum::BOT->value)->count();

        // Get all markets for the filter dropdown
        $markets = Market::where('is_active', true)
            ->get(['id', 'base_currency', 'quote_currency']);

        return view('dashboard.spot_order.index', [
            'spotOrders' => $spotOrders,
            'currentSource' => $source,
            'userOrdersCount' => $userOrdersCount,
            'botOrdersCount' => $botOrdersCount,
            'markets' => $markets
        ]);
    }

    public function calculateCommissionValues(SpotTrade $trade, TradingCommission $commission)
    {


        if ($commission->maker_commission_currency !== 'USDT') {

            $makerCommissionValue = bcmul($commission->maker_commission_amount, $trade->price, 8);

            $takerCommissionValue = $commission->taker_commission_amount;
        } else {

            $makerCommissionValue = $commission->maker_commission_amount;

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

    public function excelExport(Request $request)
    {
        $from = $request->get('from_id');
        $to = $request->get('to_id');
        $filename = 'spot_orders_' . ($from ? $from . '_' . $to : date('Y_m_d'));

        $spotOrderQuery = SpotOrder::with([
            'user',
            'market'
        ])->filterBy(request()->all());

        // Apply sorting similar to index method
        if (request()->has('sortByPrice')) {
            $sortDirection = request()->input('sortByPrice', 'desc');
            $spotOrderQuery->orderBy('price', $sortDirection);
        } elseif (request()->has('sortByFilledQuantity')) {
            $sortDirection = request()->input('sortByFilledQuantity', 'desc');
            $spotOrderQuery->orderBy('filled_quantity', $sortDirection);
        } elseif (request()->has('sortByQuantity')) {
            $sortDirection = request()->input('sortByQuantity', 'desc');
            $spotOrderQuery->orderBy('quantity', $sortDirection);
        } elseif (request()->has('sortByCreatedAt')) {
            $sortDirection = request()->input('sortByCreatedAt', 'desc');
            $spotOrderQuery->orderBy('created_at', $sortDirection);
        } else {
            $spotOrderQuery->orderBy('id', request()->input('sortById', 'desc'));
        }

        if ($request->get('from_id') && $request->get('to_id')) {
            $spotOrderQuery->where('id', '>=', $request->from_id)
                ->where('id', '<=', $request->to_id);
        }

        $spotOrders = $spotOrderQuery->get();

        $spotOrders = $spotOrders->map(function ($order) {
            return [
                $order->id,
                $order->user->fullname(),
                $order->user->email,
                $order->market->base_currency . '/' . $order->market->quote_currency,
                $order->side->label(),
                $order->type->label(),
                formatNumberTrimZeros($order->quantity),
                $order->price ? formatNumberTrimZeros($order->price) : 'بازار',
                formatNumberTrimZeros($order->filled_quantity),
                $order->status->label(),
                $order->source->label(),
                $order->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\SpotOrderExport($spotOrders), $filename . '.xlsx');
    }
}
