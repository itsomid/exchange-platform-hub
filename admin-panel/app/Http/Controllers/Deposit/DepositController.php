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
                return $deposit->amount * $deposit->currency->exchange_price; // Multiply amount by coin price
            });

        // First 5 users with the most deposits (considering currency prices)


        $topUsers = Deposit::with(['currency', 'user'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($deposits, $userId) {
                $totalDeposit = $deposits->sum(function ($deposit) {
                    return $deposit->amount * $deposit->currency->exchange_price;
                });
                return [
                    'user' => $deposits->first()->user,
                    'totalDeposit' => $totalDeposit,
                ];
            })
            ->sortByDesc('totalDeposit')
            ->take(5);
        $totalTopUsersDeposit = $topUsers->sum('totalDeposit');
//        return $topUsers;

        $deposits = Deposit::filterBy(request()->all())->with(['user','currency','currencyChain', 'transaction'])->paginate(20);


        return view('dashboard.deposits.index', [
            'deposits' => $deposits,
            'todayDepositsCount' => $todayDepositsCount,
            'totalDepositsValue' => $totalDepositsValue,
            'topUsers' => $topUsers,
            'totalTopUsersDeposit' => $totalTopUsersDeposit
        ]);
    }
}
