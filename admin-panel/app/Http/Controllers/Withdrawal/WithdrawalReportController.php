<?php

namespace App\Http\Controllers\Withdrawal;

use App\Enums\WithdrawalStatusEnum;
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

            return view('dashboard.withdraw.withdrawal-report', [
                'currencies' => $currencies,
                'chartData' => $chartData
            ]);
        }

        if ($request->from_date xor $request->to_date) {
            return redirect()->route('admin.report.withdrawal')->withErrors(['from_date' => 'یکی از تاریخ ها نمیتواند خالی باشد']);
        }

        $from_date = \App\Helpers\DateFormatter::convertPersianToCarbonDate($request->from_date);
        $to_date = \App\Helpers\DateFormatter::convertPersianToCarbonDate($request->to_date);
//        $today = Carbon::now();


        // Parse dates and set times accordingly, if provided
        // Example: "2025-01-01" -> From: 2025-01-01 00:00:00, To: 2025-01-01 23:59:59

        $completeWithdrawals = Withdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount, COUNT(id) as total_transactions, DATE(created_at) as date')
            ->where('currency_symbol', $request->currency_symbol)
            ->where('status', WithdrawalStatusEnum::COMPLETED)
            ->when($from_date, function ($query) use ($from_date) {
                $query->where('created_at', '>=', $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->where('created_at', '<=', $to_date);
            })
            ->groupBy('date', 'currency_symbol')
            ->get();

        // Pending
        $pendingWithdrawals = Withdrawal::selectRaw("
                currency_symbol,
                status,
                SUM(amount) as total_amount,
                COUNT(id) as total_transactions,
                DATE(created_at) as date
            ")
            ->where('status', WithdrawalStatusEnum::PENDING)
            ->when($request->currency_symbol, function ($q) use ($request) {
                $q->where('currency_symbol', $request->currency_symbol);
            })
            ->when($from_date, function ($query) use ($from_date) {
                $query->where('created_at', '>=', $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->where('created_at', '<=', $to_date);
            })
            ->groupBy('date', 'currency_symbol', 'status')
            ->get();

        if (is_null($completeWithdrawals)) {
            return redirect()->route('admin.report.withdrawal')->withErrors(['from_date' => 'در این بازه برداشتی ثبت نشده است']);
        }

        foreach ($completeWithdrawals as $withdrawal) {
            $chartData[$withdrawal->date]['total_amount'] = $withdrawal->total_amount;
            $chartData[$withdrawal->date]['total_transactions'] = $withdrawal->total_transactions;
        }

        $time_span = ($from_date || $to_date)
            ? (int)Carbon::parse($from_date)->diffInDays($to_date) + 1
            : 7;

        $dates = [];
        for ($i = 0; $i <= $time_span; $i++) {
            $date = Carbon::parse($to_date)->subDays($i)->toDateString();
            $dates[] = Jalalian::forge($date)->format('m/d');
            if (!array_key_exists($date, $chartData)) {
                $chartData[$date]['total_amount'] = 0;
                $chartData[$date]['total_transactions'] = 0;
            }
        }
        ksort($chartData);

        $totalExchangeFeeWithdrawals = Withdrawal::with('currency')
            ->where('currency_symbol', $request->currency_symbol)
            ->where('status', WithdrawalStatusEnum::COMPLETED)
            ->when($from_date, function ($query) use ($from_date) {
                $query->where('created_at', '>=', $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->where('created_at', '<=', $to_date);
            })
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->exchange_fee; // Multiply amount by coin price
            });

        $totalCompleteWithdrawalsValue = Withdrawal::with('currency')
            ->where('currency_symbol', $request->currency_symbol)
            ->where('status', WithdrawalStatusEnum::COMPLETED)
            ->when($from_date, function ($query) use ($from_date) {
                $query->where('created_at', '>=', $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->where('created_at', '<=', $to_date);
            })
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->amount * $withdraw->currency->exchange_price; // Multiply amount by coin price
            });

        $totalPendingWithdrawalsValue = Withdrawal::with('currency')
            ->where('currency_symbol', $request->currency_symbol)
            ->where('status', WithdrawalStatusEnum::PENDING)
            ->when($from_date, function ($query) use ($from_date) {
                $query->where('created_at', '>=', $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->where('created_at', '<=', $to_date);
            })
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->amount * $withdraw->currency->exchange_price; // Multiply amount by coin price
            });




//        dd($dates , $chartData) ;
        return view('dashboard.withdraw.withdrawal-report', [
            'completeWithdrawals' => $completeWithdrawals,
            'pendingWithdrawals' => $pendingWithdrawals,
            'currencies' => $currencies,
            'dates' => array_reverse($dates),
            'chartData' => $chartData,
            'totalCompleteWithdrawalsValue' => $totalCompleteWithdrawalsValue,
            'totalPendingWithdrawalsValue' => $totalPendingWithdrawalsValue,
            'totalExchangeFeeWithdrawals' => $totalExchangeFeeWithdrawals
        ]);

    }
}
