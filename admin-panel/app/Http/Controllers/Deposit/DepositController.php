<?php

namespace App\Http\Controllers\Deposit;

use App\Exports\DepositExport;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DepositController extends Controller
{
    public function index()
    {
        $today = now()->toDateString(); // Get today's date

        // Count of today's deposits
        $todayDepositsCount = Deposit::whereDate('created_at', $today)->count();

        $todayDepositsValue = Deposit::with('currency')
            ->whereDate('created_at', $today)// Assuming `currency` has the price
            ->get()
            ->sum(function ($deposit) {
                return $deposit->amount * ($deposit->currency?->exchange_price ?? 0); // Multiply amount by coin price
            });

        $totalDepositsValue = Deposit::with('currency')
            ->get()
            ->sum('usdt_value');
        // First 5 users with the most deposits (considering currency prices)


        $topUsers = Deposit::with(['currency', 'user'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($deposits, $userId) {
                $totalDeposit = $deposits->sum('usdt_value');
                return [
                    'user' => $deposits->first()->user,
                    'totalDeposit' => $totalDeposit,
                ];
            })
            ->sortByDesc('totalDeposit')
            ->take(5);


        $totalTopUsersDeposit = $topUsers->sum('totalDeposit');
//        return $topUsers;

        $deposits = Deposit::filterBy(request()->all())->with(['user','currency','currencyChain', 'transaction'])
            ->orderBy('id', request()->input('sortById', 'desc'))
            ->paginate(20);

        return view('dashboard.deposits.index', [
            'deposits' => $deposits,
            'todayDepositsValue' => $todayDepositsValue,
            'totalDepositsValue' => $totalDepositsValue,
            'todayDepositsCount' => $todayDepositsCount,
            'totalTopUsersDeposit' => $totalTopUsersDeposit,
            'topUsers' => $topUsers,
        ]);
    }

    public function excelExport(Request $request)
    {
        $from = $request->get('from_id');
        $to = $request->get('to_id');
        $filename = 'deposits_' . $from . '_' . $to;

        $depositQuery = Deposit::orderBy('id')->filterBy(request()->all());
        if ($request->get('from_id') && $request->get('to_id')) {
            $depositQuery->where('id', '>=', $request->from_id)
                ->where('id', '<=', $request->to_id);
        }
        $deposits = $depositQuery->get();

        $deposits = $deposits->map(function (Deposit $deposit) {

            return [
                $deposit->id,
                $deposit->user->email,
                $deposit->currency_symbol,
                $deposit->currencyChain->chain_name,
                formatNumberTrimZeros($deposit->amount),
                formatNumberTrimZeros($deposit->usdt_value),
                $deposit->address,
                $deposit->transaction_hash,
                DateFormatter::convertToPersianDate($deposit->created_at,'%Y/%m/%d H:i:s'),
                DateFormatter::convertToPersianDate($deposit->confirmed_at,'%Y/%m/%d H:i:s'),
                $deposit->status->label(),
                $deposit->description ,
            ];
        });

        return Excel::download(new DepositExport($deposits), $filename . '.xlsx');
    }
}
