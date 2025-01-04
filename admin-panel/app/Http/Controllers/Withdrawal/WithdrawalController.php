<?php

namespace App\Http\Controllers\Withdrawal;

use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function index()
    {

        $today = now()->toDateString(); // Get today's date

        // Count of today's deposits
        $todayWithdrawalsCount = Withdrawal::whereDate('created_at', $today)->count();

        $totalWithdrawalsValue = Withdrawal::with('currency')
            ->whereDate('created_at', $today)// Assuming `currency` has the price
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->amount * $withdraw->currency->exchange_price; // Multiply amount by coin price
            });

        // First 5 users with the most deposits (considering currency prices)


        $topUsers = Withdrawal::with(['currency', 'user'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($withdraws, $userId) {
                $totalWithdraws = $withdraws->sum(function ($deposit) {
                    return $deposit->amount * $deposit->currency->exchange_price;
                });
                return [
                    'user' => $withdraws->first()->user,
                    'totalWithdraw' => $totalWithdraws,
                ];
            })
            ->sortByDesc('totalDeposit')
            ->take(5);
        $totalTopUsersWithdrawals = $topUsers->sum('totalWithdraws');
//        return $topUsers;

        $withdraws = Withdrawal::filterBy(request()->all())->with(['user', 'currency', 'transaction'])->paginate(20);


        return view('dashboard.withdraw.index', [
            'withdraws' => $withdraws,
            'todayWithdrawalsCount' => $todayWithdrawalsCount,
            'totalWithdrawalsValue' => $totalWithdrawalsValue,
            'topUsers' => $topUsers,
            'totalTopUsersWithdrawals' => $totalTopUsersWithdrawals
        ]);
    }
}
