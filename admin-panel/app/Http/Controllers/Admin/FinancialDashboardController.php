<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OTCOrder;
use App\Models\SpotTrade;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialDashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.financial.index');
    }

    public function getTradeStats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // --- Today Trade Volume (or custom range) ---
        $todayStart = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
        $todayEnd = $endDate ? Carbon::parse($endDate)->endOfDay() : now();

        $todaySpotVolume = SpotTrade::whereBetween('created_at', [$todayStart, $todayEnd])
            ->with('market.quoteCurrency')
            ->get()
            ->sum(function ($trade) {
                return $trade->quantity * $trade->price * ($trade->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $todayOTCVolume = OTCOrder::whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', 'success')
            ->with('market.quoteCurrency')
            ->get()
            ->sum(function ($order) {
                return $order->quantity * $order->price * ($order->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $todayTradeVolume = $todaySpotVolume + $todayOTCVolume;

        // --- All Time Trade Volume ---
        $allTimeSpotVolume = SpotTrade::with('market.quoteCurrency')
            ->get()
            ->sum(function ($trade) {
                return $trade->quantity * $trade->price * ($trade->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $allTimeOTCVolume = OTCOrder::where('status', 'success')
            ->with('market.quoteCurrency')
            ->get()
            ->sum(function ($order) {
                return $order->quantity * $order->price * ($order->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $allTimeTradeVolume = $allTimeSpotVolume + $allTimeOTCVolume;

        // --- Number of Trades Today (or custom date) ---
        $tradeDate = $request->input('trade_date');
        $tradeDayStart = $tradeDate ? Carbon::parse($tradeDate)->startOfDay() : now()->startOfDay();
        $tradeDayEnd = $tradeDate ? Carbon::parse($tradeDate)->endOfDay() : now();

        $todaySpotCount = SpotTrade::whereBetween('created_at', [$tradeDayStart, $tradeDayEnd])->count();
        $todayOTCCount = OTCOrder::whereBetween('created_at', [$tradeDayStart, $tradeDayEnd])
            ->where('status', 'success')
            ->count();
        $todayTradeCount = $todaySpotCount + $todayOTCCount;

        // --- All Time Trade Count ---
        $allTimeSpotCount = SpotTrade::count();
        $allTimeOTCCount = OTCOrder::where('status', 'success')->count();
        $allTimeTradeCount = $allTimeSpotCount + $allTimeOTCCount;

        return response()->json([
            'todayTradeVolume' => formatNumberTrimZeros($todayTradeVolume, 2),
            'todaySpotVolume' => formatNumberTrimZeros($todaySpotVolume, 2),
            'todayOTCVolume' => formatNumberTrimZeros($todayOTCVolume, 2),
            'allTimeTradeVolume' => formatNumberTrimZeros($allTimeTradeVolume, 2),
            'allTimeSpotVolume' => formatNumberTrimZeros($allTimeSpotVolume, 2),
            'allTimeOTCVolume' => formatNumberTrimZeros($allTimeOTCVolume, 2),
            'todayTradeCount' => number_format($todayTradeCount),
            'todaySpotCount' => number_format($todaySpotCount),
            'todayOTCCount' => number_format($todayOTCCount),
            'allTimeTradeCount' => number_format($allTimeTradeCount),
            'allTimeSpotCount' => number_format($allTimeSpotCount),
            'allTimeOTCCount' => number_format($allTimeOTCCount),
        ]);
    }
}
