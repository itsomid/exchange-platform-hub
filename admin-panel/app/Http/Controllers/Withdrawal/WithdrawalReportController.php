<?php

namespace App\Http\Controllers\Withdrawal;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class WithdrawalReportController extends Controller
{
    public function index(Request $request)
    {
        $currencies = Currency::all();
        $chartData = [];

        if (!$request->from_date || !$request->to_date) {

            return view('dashboard.report.withdrawal', [
                'currencies' => $currencies,
                'chartData' => $chartData
            ]);
        }

        if ($request->from_date xor $request->to_date) {
            return redirect()->route('dashboard.report.withdrawal')->withErrors(['from_date' => 'یکی از تاریخ ها نمیتواند خالی باشد']);
        }

        $from_date = \App\Helpers\DateFormatter::convertPersianToCarbonDate($request->from_date);
        $to_date = \App\Helpers\DateFormatter::convertPersianToCarbonDate($request->to_date);
        $today = Carbon::now();


        // Parse dates and set times accordingly, if provided
        // Example: "2025-01-01" -> From: 2025-01-01 00:00:00, To: 2025-01-01 23:59:59

        $withdrawals = Withdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount, COUNT(id) as total_transactions, DATE(created_at) as date')
            ->where('currency_symbol', $request->currency_symbol)
            ->when($from_date, function ($query) use ($from_date) {
                $query->where('created_at', '>=', $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->where('created_at', '<=', $to_date);
            })
            ->groupBy('date', 'currency_symbol')
            ->get();




        foreach ($withdrawals as $withdrawal) {
            $chartData[$withdrawal->date] = $withdrawal->total_amount;
        }

        $time_span = ($from_date || $to_date)
            ? (int)Carbon::parse($from_date)->diffInDays($to_date) + 1
            : 7;

        $dates = [];
        for ($i = 0; $i <= $time_span; $i++) {
            $date = $today->copy()->subDays($i)->toDateString();
            $dates[] = Jalalian::forge($date)->format('m/d');
            if (!array_key_exists($date, $chartData)) {
                $chartData[$date] = 0;
            }
        }
        ksort($chartData);
//        dd($dates , $chartData) ;
        return view('dashboard.report.withdrawal', [
            'withdrawals'=>$withdrawals,
            'currencies' => $currencies,
            'dates' => array_reverse($dates),
            'chartData' => $chartData,
        ]);

    }
}
