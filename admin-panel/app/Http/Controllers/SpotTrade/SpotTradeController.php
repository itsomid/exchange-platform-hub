<?php

namespace App\Http\Controllers\SpotTrade;

use App\Http\Controllers\Controller;
use App\Models\SpotTrade;
use App\Models\Market;
use Illuminate\Http\Request;
use App\Models\TradingCommission;

class SpotTradeController extends Controller
{
    public function index()
    {
        $query = SpotTrade::filterBy(request()->all())->with([
            'market',
            'makerOrder.user',
            'takerOrder.user',
            'commission'
        ]);

        // Handle sorting
        if (request()->has('sortByTradeValue')) {
            // Sort by trade value (price * quantity)
            $sortDirection = request()->input('sortByTradeValue', 'desc');
            $query->selectRaw('spot_trades.*, (price * quantity) as trade_value')
                ->orderBy('trade_value', $sortDirection);
        } elseif (request()->has('sortByQuantity')) {
            // Sort by quantity
            $sortDirection = request()->input('sortByQuantity', 'desc');
            $query->orderBy('quantity', $sortDirection);
        } else {
            // Default sorting by ID
            $query->orderBy('id', request()->input('sortById', 'desc'));
        }

        $spotTrades = $query->paginate(50);

        // Calculate commission values for each trade
        $spotTrades->each(function ($trade) {
            if ($trade->commission) {
                $commissionValues = $this->calculateCommissionValues($trade, $trade->commission);
                $trade->maker_commission_value = $commissionValues['maker_commission_value'];
                $trade->taker_commission_value = $commissionValues['taker_commission_value'];
                $trade->total_commission_value = $commissionValues['total_commission_value'];
            }
        });

        // Get all markets for the filter dropdown
        $markets = Market::where('is_active', true)
            ->get(['id', 'base_currency', 'quote_currency']);

        return view('dashboard.spot_trade.index', [
            'spotTrades' => $spotTrades,
            'markets' => $markets
        ]);
    }

    public function excelExport(Request $request)
    {
        $from = $request->get('from_id');
        $to = $request->get('to_id');
        $filename = 'spot_trades_' . $from . '_' . $to;

        $spotTradeQuery = SpotTrade::orderBy('id')->filterBy(request()->all())->with([
            'market',
            'makerOrder.user',
            'takerOrder.user',
            'commission'
        ]);

        if ($request->get('from_id') && $request->get('to_id')) {
            $spotTradeQuery->where('id', '>=', $request->from_id)
                ->where('id', '<=', $request->to_id);
        }

        $spotTrades = $spotTradeQuery->get();

        $spotTrades = $spotTrades->map(function ($trade) {
            // Calculate commission values
            $commissionValues = [
                'maker_commission_value' => 0,
                'taker_commission_value' => 0,
                'total_commission_value' => 0
            ];

            if ($trade->commission) {
                $commissionValues = $this->calculateCommissionValues($trade, $trade->commission);
            }

            return [
                $trade->id,
                $trade->market->base_currency . '/' . $trade->market->quote_currency,
                $trade->makerOrder->user->email,
                $trade->takerOrder->user->email,
                formatNumberTrimZeros($trade->quantity),
                formatNumberTrimZeros($trade->price),
                formatNumberTrimZeros(bcmul($trade->quantity, $trade->price, 8)),
                formatNumberTrimZeros($commissionValues['maker_commission_value']),
                formatNumberTrimZeros($commissionValues['taker_commission_value']),
                formatNumberTrimZeros($commissionValues['total_commission_value']),
                $trade->makerOrder->side->label(),
                $trade->takerOrder->side->label(),
                $trade->makerOrder->status->label(),
                $trade->takerOrder->status->label(),
                $trade->created_at,
            ];
        });

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\SpotTradeExport($spotTrades), $filename . '.xlsx');
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

    public function updateNote(Request $request, SpotTrade $spotTrade)
    {
        $request->validate(['notes' => 'nullable|string|max:5000']);
        $spotTrade->update(['notes' => $request->input('notes')]);

        return response()->json(['success' => true, 'message' => 'نوت با موفقیت ذخیره شد.']);
    }
}
