<?php

namespace App\Http\Controllers\Deposit;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function index()
    {
        $today = now()->toDateString(); // Get today's date

        // Count of today's deposits
        $todayDepositsCount = Deposit::whereDate('created_at', $today)->count();

        $totalDepositsValue = Deposit::with('currency')
        ->whereDate('created_at', $today)// Assuming `currency` has the price
        ->get()
            ->sum(function ($deposit) {
                return $deposit->amount * $deposit->currency->baseMarkets->activeExchangePrice->price; // Multiply amount by coin price
            });


        $deposits = Deposit::with(['currency', 'transaction'])->get();
        return view('dashboard.deposits.index', [
            'deposits' => $deposits,
            'todayDepositsCount' => $todayDepositsCount,
            'totalDepositsValue' => $totalDepositsValue
        ]);
    }
}
