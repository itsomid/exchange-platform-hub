<?php

namespace App\Http\Controllers\SpotOrder;

use App\Http\Controllers\Controller;
use App\Models\SpotTrade;
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
             'commission'])
//            ->orderBy('id', request()->input('sortById', 'desc'))
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

        return view('dashboard.spot_trade.index',[
            'spotTrades' => $spotTrades
        ]);
    }

    public function excelExport()
    {
        return 1;
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
