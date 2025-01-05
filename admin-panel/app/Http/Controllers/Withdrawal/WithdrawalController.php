<?php

namespace App\Http\Controllers\Withdrawal;

use App\Enums\TransactionTypeEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

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
        $totalTopUsersWithdrawals = $topUsers->sum('totalWithdraw');

        $withdraws = Withdrawal::filterBy(request()->all())->with(['user', 'currency', 'transaction'])->paginate(20);


        return view('dashboard.withdraw.index', [
            'withdraws' => $withdraws,
            'todayWithdrawalsCount' => $todayWithdrawalsCount,
            'totalWithdrawalsValue' => $totalWithdrawalsValue,
            'topUsers' => $topUsers,
            'totalTopUsersWithdrawals' => $totalTopUsersWithdrawals
        ]);
    }

    /**
     * @throws \Exception
     */
    public function confirmWithdrawal(Withdrawal $withdraw)
    {
        try {
            $admin_id = \Auth::user()->id;
            $this->withdrawalService->adminApproveWithdrawal($withdraw->id, $admin_id);

            Toast::message('.تایید برداشت با موفقیت انجام شد')->success()->notify();
            return redirect()->back();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withErrors(['general' => $exception->getMessage()]);
        }
    }

    public function cancelWithdrawal(Withdrawal $withdraw)
    {
        try {
            $admin_id = \Auth::user()->id;
            $this->withdrawalService->adminCancelWithdrawal($withdraw->id, $admin_id);

            Toast::message('.تایید برداشت با موفقیت انجام شد')->success()->notify();
            return redirect()->back();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withErrors(['general' => $exception->getMessage()]);
        }
    }
}
