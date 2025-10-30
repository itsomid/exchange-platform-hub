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
        $spotTrades = SpotTrade::filterBy(request()->all())->with([
            'market',
            'makerOrder.user',
            'takerOrder.user',
            'commission'
        ])
            ->orderBy('id', request()->input('sortById', 'desc'))
            ->paginate(50);

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

    public function excelExport()
    {
        return 1;
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
}
