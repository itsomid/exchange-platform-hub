<?php

namespace App\Http\Controllers\Withdrawal;

use App\Enums\WithdrawalStatusEnum;
use App\Exports\DepositExport;
use App\Exports\WithdrawalExport;
use App\Functions\FlashMessages\Toast;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Withdrawal;

use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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
            ->whereDate('created_at', $today) // Assuming `currency` has the price
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->amount * $withdraw->currency->exchange_price; // Multiply amount by coin price
            });

        // First 5 users with the most deposits (considering currency prices)

        $topUsers = Withdrawal::with(['currency', 'user'])
            ->where('status', WithdrawalStatusEnum::COMPLETED)
            ->whereDate('created_at', $today)
            ->get()
            ->groupBy('user_id')
            ->map(function ($withdraws, $userId) {
                $totalWithdraws = $withdraws->sum(function ($withdraw) {
                    return $withdraw->amount * $withdraw->currency->exchange_price;
                });

                return [
                    'user' => $withdraws->first()->user,
                    'totalWithdraw' => $totalWithdraws,
                ];
            })
            ->sortByDesc('totalDeposit')
            ->take(5);
        $totalTopUsersWithdrawals = $topUsers->sum('totalWithdraw');

        $withdraws = Withdrawal::filterBy(request()->all())->with(['user', 'currency', 'transaction'])
            ->orderBy('id', request()->input('sortById', 'desc'))
            ->paginate(20);

        return view('dashboard.withdraw.index', [
            'withdraws' => $withdraws,
            'todayWithdrawalsCount' => $todayWithdrawalsCount,
            'totalWithdrawalsValue' => $totalWithdrawalsValue,
            'topUsers' => $topUsers,
            'totalTopUsersWithdrawals' => $totalTopUsersWithdrawals,
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

            Toast::message('.وضعیت برداشت به مورد تایید نیست تغییر کرد')->success()->notify();

            return redirect()->back();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withErrors(['general' => $exception->getMessage()]);
        }
    }

    public function checkWithdrawal(Withdrawal $withdrawal)
    {
        try {
            $withdrawalService = resolve(\App\Services\Withdrawal\WithdrawalService::class);

            // Check if withdrawal is in pending status
            if ($withdrawal->status !== \App\Enums\WithdrawalStatusEnum::PENDING) {
                Toast::message('این برداشت در وضعیت انتظار نیست.')->warning()->notify();
                return redirect()->back();
            }

            // Check the specific withdrawal
            $withdrawalCollection = new \Illuminate\Database\Eloquent\Collection([$withdrawal]);
            $response = $withdrawalService->checkWithdrawal($withdrawalCollection);

            if ($response->getStatus() === null) {
                Toast::message('هیچ تغییری در وضعیت برداشت یافت نشد.')->info()->notify();
            } elseif ($response->getStatus() === \App\Enums\WithdrawalStatusEnum::COMPLETED) {
                Toast::message('برداشت با موفقیت تکمیل شد.')->success()->notify();
            } elseif ($response->getStatus() === \App\Enums\WithdrawalStatusEnum::FAILED) {
                Toast::message('برداشت با خطا مواجه شد.')->danger()->notify();
            } else {
                Toast::message('فرآیند چک برداشت آغاز شد.')->success()->notify();
            }

            return redirect()->back();
        } catch (\Throwable $exception) {
            report($exception);
            Toast::message('خطا در بررسی برداشت: ' . $exception->getMessage())->danger()->notify();
            return redirect()->back();
        }
    }

    public function excelExport(Request $request)
    {
        $from = $request->get('from_id');
        $to = $request->get('to_id');
        $filename = 'withdrawal_' . $from . '_' . $to;

        $withdrawalQuery = Withdrawal::orderBy('id')->filterBy(request()->all());
        if ($request->get('from_id') && $request->get('to_id')) {
            $withdrawalQuery->where('id', '>=', $request->from_id)
                ->where('id', '<=', $request->to_id);
        }
        $withdrawals = $withdrawalQuery->get();

        $withdrawals = $withdrawals->map(function (Withdrawal $withdrawal) {

            return [
                $withdrawal->id,
                $withdrawal->user->email,
                $withdrawal->currency_symbol,
                $withdrawal->currencyChain->chain_name,
                formatNumberTrimZeros($withdrawal->amount),
                formatNumberTrimZeros($withdrawal->usdt_value),
                formatNumberTrimZeros($withdrawal->amount - $withdrawal->total_fee),
                formatNumberTrimZeros($withdrawal->exchange_fee),
                formatNumberTrimZeros($withdrawal->network_fee),
                $withdrawal->address,
                $withdrawal->transaction_hash,
                DateFormatter::convertToPersianDate($withdrawal->created_at, '%Y/%m/%d H:i:s'),
                DateFormatter::convertToPersianDate($withdrawal->confirmed_at, '%Y/%m/%d H:i:s'),
                $withdrawal->status->label(),
                $withdrawal->description,
            ];
        });

        return Excel::download(new WithdrawalExport($withdrawals), $filename . '.xlsx');
    }
}
