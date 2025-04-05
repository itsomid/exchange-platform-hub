<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\ExchangeTransaction;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Carbon\Carbon;


class HomeController extends Controller
{
    protected $exchangeUserId;

    public function __cunstruct()
    {
        $this->exchangeUserId = config('exchange.exchange_user_id', 1);
    }

    public function index()
    {
        $admin = auth()->guard('admin')->user();
        if (empty($admin->two_factor_secret) && app()->environment() == 'production') {
            $is2FAEnabled = false;
        } else {
            $is2FAEnabled = true;
        }

        $today = now()->toDateString(); // Get today's date

        //مجموع برداشت های کاربران
        $withdrawalSums = Withdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount')
            ->where('status', WithdrawalStatusEnum::COMPLETED)
            ->where('user_id', '!=', $this->exchangeUserId)
            ->groupBy('currency_symbol')
            ->get();


        //سود صرافی از محل کارمزدهای OTC
        $OTCFeeTransactionsByCurrency = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::OTC)
            ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->selectRaw('wallets.currency_symbol, SUM(transactions.amount) as total_amount, COUNT(transactions.id) as transaction_count')
            ->groupBy('wallets.currency_symbol')
            ->get();


        //سود صرافی از محل کارمزدهای برداشت
        $withdrawalFeeTransactionsByCurrency = Transaction::where('type', TransactionTypeEnum::FEE)
            ->where('subtype', TransactionSubTypeEnum::WITHDRAWAL_EXCHANGE_FEE)
            ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->selectRaw('wallets.currency_symbol, SUM(transactions.amount) as total_amount, COUNT(transactions.id) as transaction_count')
            ->groupBy('wallets.currency_symbol')
            ->get();

        //مجموع خرید از صرافی مرجع
        $boughtHistoryByCurrency = ExchangeTransaction::with('currency')
            ->selectRaw('currency_symbol, SUM(amount) as total_amount')
            ->groupBy('currency_symbol')
            ->get()
            ->map(function ($transaction) {
                $transaction->total_filled_value = ExchangeTransaction::where('currency_symbol', $transaction->currency_symbol)
                    ->get()
                    ->sum(function ($t) {
                        return (float)data_get($t, 'response.data.filled_value', 0);
                    });

                return $transaction;
            });


        //مجموع برداشت از صرافی مرجع
        $refExchangeWithdrawalSum = ExchangeAssetsWithdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount')
            ->groupBy('currency_symbol')
            ->get();


        $totalDepositsValue = Deposit::with('currency')
            ->whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek()])
            ->get()
            ->sum(function ($deposit) {
                return $deposit->amount * $deposit->currency->exchange_price;
            });

        $totalWithdrawalValue = Withdrawal::with('currency')
            ->whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek()])
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->amount * $withdraw->currency->exchange_price;
            });


        return view('dashboard.home.index', [
            'OTCFeeTransactionsByCurrency' => $OTCFeeTransactionsByCurrency,
            'withdrawalFeeTransactionsByCurrency' => $withdrawalFeeTransactionsByCurrency,
            'withdrawalSums' => $withdrawalSums,
            'boughtHistoryByCurrency' => $boughtHistoryByCurrency,
            'totalDepositsValue' => $totalDepositsValue,
            'totalWithdrawalValue' => $totalWithdrawalValue,
            'refExchangeWithdrawalSum' => $refExchangeWithdrawalSum,
            'is2FAEnabled' => $is2FAEnabled
        ]);
    }


}
