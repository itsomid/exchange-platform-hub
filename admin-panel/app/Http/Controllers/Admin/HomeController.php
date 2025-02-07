<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\User;
use Carbon\Carbon;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

class HomeController extends Controller
{
    public function index()
    {

        $today = now()->toDateString(); // Get today's date


        $OTCFeeTransactionsByCurrency = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::OTC)
            ->selectRaw('wallet_id, SUM(amount) as total_amount')
            ->groupBy('wallet_id')
            ->with('wallet.currency')
            ->get();

        $withdrawalFeeTransactionsByCurrency = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::WITHDRAWAL_FEE)
            ->selectRaw('wallet_id, SUM(amount) as total_amount')
            ->groupBy('wallet_id')
            ->with('wallet.currency')
            ->get();

        $withdrawalSums = Withdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount')
            ->where('status', WithdrawalStatusEnum::COMPLETED)
            ->groupBy('currency_symbol')
            ->get();

        $totalDepositsValue = Deposit::with('currency')
            ->whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek(Carbon::FRIDAY)])
            ->get()
            ->sum(function ($deposit) {
                return $deposit->amount * $deposit->currency->exchange_price;
            });

        $totalWithdrawalValue = Withdrawal::with('currency')
            ->whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek(Carbon::FRIDAY)])
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->amount * $withdraw->currency->exchange_price;
            });

        $year = Jalalian::now()->getYear(); // Get current Jalali year
        $month = Jalalian::now()->getMonth(); // Get current Jalali year

        $startOfMonth = Jalalian::fromFormat('Y-m-d', "$year-$month-01")->toCarbon();

        // Get the end of the month safely
        $endOfMonth = $startOfMonth->copy()->addMonth()->subDay();

         $registrations = User::query()
            ->whereBetween('registration_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->map(function ($reg) {
                return [
                    'day' => Jalalian::fromDateTime($reg->registration_date)->getDay(),
                    'total' => 1
                ];
            })
            ->groupBy('day') // گروه‌بندی براساس روز شمسی
            ->map(function ($items, $day) {
                return [
                    'day' => (int)$day,
                    'total' => $items->sum('total')
                ];
            })
            ->values(); // برای حذف کلیدهای عددی

        $inactiveRegistrations = User::query()
            ->whereBetween('registration_date', [$startOfMonth, $endOfMonth])
            ->where('status', UserStatusEnum::INACTIVE->value)
            ->get()
            ->map(function ($reg) {
                return [
                    'day' => Jalalian::fromDateTime($reg->registration_date)->getDay(),
                    'total' => 1
                ];
            })
            ->groupBy('day') // گروه‌بندی براساس روز شمسی
            ->map(function ($items, $day) {
                return [
                    'day' => (int)$day,
                    'total' => $items->sum('total')
                ];
            })
            ->values(); // برای حذف کلیدهای عددی


        $daysInMonth = 30; // Adjust based on the selected Persian month
        $totalRegistrationData = array_fill(1, $daysInMonth, 0);
        $totalInActiveRegistrationData = array_fill(1, $daysInMonth, 0);

        foreach ($registrations as $reg) {
            $totalRegistrationData[(int)$reg['day']] = $reg['total'];
        }

        foreach ($inactiveRegistrations as $reg) {
            $totalInActiveRegistrationData[(int)$reg['day']] = $reg['total'];
        }

        return view('dashboard.home.index', [
            'OTCFeeTransactionsByCurrency' => $OTCFeeTransactionsByCurrency,
            'withdrawalFeeTransactionsByCurrency' => $withdrawalFeeTransactionsByCurrency,
            'withdrawalSums' => $withdrawalSums,
            'totalDepositsValue' => $totalDepositsValue,
            'totalWithdrawalValue' => $totalWithdrawalValue,
            'totalRegistrationData' => $totalRegistrationData,
            'totalInActiveRegistrationData' => $totalInActiveRegistrationData,
        ]);
    }

}
