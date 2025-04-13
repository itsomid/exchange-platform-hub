<?php

namespace App\Http\Controllers\SpotOrder;

use App\Http\Controllers\Controller;
use App\Models\SpotTrade;
use Illuminate\Http\Request;

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

        return view('dashboard.spot_trade.index',[
            'spotTrades' => $spotTrades
        ]);
    }

    public function excelExport()
    {
        return 1;
    }
}
