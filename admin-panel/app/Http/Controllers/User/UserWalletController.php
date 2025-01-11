<?php

namespace App\Http\Controllers\User;

use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;

class UserWalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function userWallets(User $user)
    {
        $user = User::findOrFail($user->id);

        $wallets = $user->wallets;

        $totalAssetsValue = $this->walletService->totalAssetsValue($user);

        // Calculate the value of each wallet's currency
         $walletsWithAssetsValues = $wallets->map(function ($wallet) {
            $specificAssetValue = $this->walletService->specificAssetValue($wallet->user, $wallet->currency_symbol);
            $wallet->assetValue = $specificAssetValue; // Add the value to the wallet object
            return $wallet;
        })->sortByDesc('assetValue');

        return view('dashboard.wallet.user-wallets', [
            'wallets' => $walletsWithAssetsValues,
            'user' => $user,
            'totalAssetsValue' => $totalAssetsValue
        ]);
    }

    public function walletDetails(User $user, Wallet $wallet, $type)
    {

        $specificAssetValue = $this->walletService->specificAssetValue($user, $wallet->currency_symbol);
        $transactionTypes = [
            'deposit' => ['title' => 'واریز', 'data' => $wallet],
            'withdrawal' => ['title' => 'برداشت', 'data' => $wallet],
            'buy' => ['title' => 'خرید OTC', 'data' => $wallet],
            'sell' => ['title' => 'فروش OTC', 'data' => $wallet],
        ];
        $transactionTitle = $transactionTypes[$type]['title'] ?? 'Transactions';
        // Fetch transactions based on type
        if ($type === 'deposit') {
            $transactions = Transaction::whereIn('type', [TransactionTypeEnum::DEPOSIT])
                ->where('wallet_id', $wallet->id)
                ->get();

        } else {
            $transactions = Transaction::where('type', $type)
                ->where('wallet_id', $wallet->id)
                ->get();
        }

        // Fetch the total deposit amount and the last deposit date
        $totalDeposits = Transaction::whereIn('type', [TransactionTypeEnum::DEPOSIT])
            ->where('wallet_id', $wallet->id)
            ->sum('amount');

        $totalDepositsValue = $this->walletService->totalTransactionValueBasedType($wallet->currency_symbol, [TransactionTypeEnum::DEPOSIT]);

        $lastDeposit = Transaction::whereIn('type', [TransactionTypeEnum::DEPOSIT])
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->first();

        $lastDepositDate = $lastDeposit ? \App\Helpers\DateFormatter::convertToPersianDate($lastDeposit->created_at, '%d %B %Y') : 'بدون واریزی';


        // Fetch the total withdraw amount and the last withdraw date
        $totalWithdraws = Transaction::where('type', TransactionTypeEnum::WITHDRAWAL)
            ->where('wallet_id', $wallet->id)
            ->sum('amount');

        $totalWithdrawValue = $this->walletService->totalTransactionValueBasedType($wallet->currency_symbol, [TransactionTypeEnum::WITHDRAWAL]);

        $lastWithdraw = Transaction::where('type', TransactionTypeEnum::WITHDRAWAL)
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->first();

        $lastWithdrawDate = $lastWithdraw ? \App\Helpers\DateFormatter::convertToPersianDate($lastWithdraw->created_at, '%d %B %Y') : 'بدون برداشت';

        // Fetch the total OTC sell and buy amounts and last transaction dates
        $totalOtcSell = Transaction::where('type', 'sell')
            ->where('wallet_id', $wallet->id)
            ->sum('amount');

        $totalOtcSellValue = $this->walletService->totalTransactionValueBasedType($wallet->currency_symbol, [TransactionTypeEnum::SELL]);

        $lastOtcSell = Transaction::where('type', 'sell')
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->first();

        $lastOtcSellDate = $lastOtcSell ? \App\Helpers\DateFormatter::convertToPersianDate($lastOtcSell->created_at, '%d %B %Y') : 'بدون فروش OTC';

        $totalOtcBuyValue = $this->walletService->totalTransactionValueBasedType($wallet->currency_symbol, [TransactionTypeEnum::BUY]);

        $totalOtcBuy = Transaction::where('type', 'buy')
            ->where('wallet_id', $wallet->id)
            ->sum('amount');

        $lastOtcBuy = Transaction::where('type', 'buy')
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->first();

        $lastOtcBuyDate = $lastOtcBuy ? \App\Helpers\DateFormatter::convertToPersianDate($lastOtcBuy->created_at, '%d %B %Y') : 'بدون خرید OTC';

        return view('dashboard.wallet.wallet-transaction-details', [
            'user' => $user,
            'wallet' => $wallet,
            'specificAssetValue' => $specificAssetValue,
            'transactionTitle' => $transactionTitle,
            'transactions' => $transactions,
            'totalDeposits' => $totalDeposits,
            'totalDepositsValue' => $totalDepositsValue,
            'lastDepositDate' => $lastDepositDate,
            'totalWithdraws' => $totalWithdraws,
            'totalWithdrawValue' => $totalWithdrawValue,
            'lastWithdrawDate' => $lastWithdrawDate,
            'totalOtcSell' => $totalOtcSell,
            'totalOtcSellValue' => $totalOtcSellValue,
            'lastOtcSellDate' => $lastOtcSellDate,
            'totalOtcBuy' => $totalOtcBuy,
            'totalOtcBuyValue' => $totalOtcBuyValue,
            'lastOtcBuyDate' => $lastOtcBuyDate
        ]);
    }

    public function blockAssetBalance()
    {

    }

}
