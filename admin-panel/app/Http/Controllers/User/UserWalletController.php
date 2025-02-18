<?php

namespace App\Http\Controllers\User;

use App\Enums\DepositStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\Deposit\DepositService;
use App\Services\OTC\OTCService;
use App\Services\Transaction\TransactionService;
use App\Services\Wallet\WalletService;
use App\Services\Withdrawal\WithdrawalService;

class UserWalletController extends Controller
{
    protected $walletService;
    protected $depositService;
    protected $withdrawalService;
    protected $otcService;
    protected $transactionService;
    protected $exchangeUserId;

    public function __construct(WalletService $walletService, DepositService $depositService, WithdrawalService $withdrawalService, OTCService $otcService, TransactionService $transactionService)
    {
        $this->walletService = $walletService;
        $this->depositService = $depositService;
        $this->withdrawalService = $withdrawalService;
        $this->otcService = $otcService;
        $this->transactionService = $transactionService;
        $this->exchangeUserId = config('exchange.exchange_user_id', 1);
    }

    public function userWallets(User $user)
    {
        $user = User::findOrFail($user->id);


        $wallets = $user->wallets()->with('walletChains')->get();

        $totalAssetsValue = $this->walletService->totalAssetsValue($user);
        $totalAvailableAssetsValue = $this->walletService->totalAvailableAssetsValue($user);
        $totalBlockedAssetsValue = $this->walletService->totalBlockedAssetsValue($user);

        // Calculate the value of each wallet's currency
        $walletsWithAssetsValues = $wallets->map(function ($wallet) {
            $specificAssetValue = $this->walletService->specificAssetValue($wallet->user, $wallet->currency_symbol);
            $wallet->assetValue = $specificAssetValue; // Add the value to the wallet object
            return $wallet;
        })->sortByDesc('assetValue');

        $totalDepositsValue = Deposit::with('currency')
            ->whereUserId($user->id)
            ->get()
            ->sum('usdt_value');

        $totalWithdrawalsValue = Withdrawal::with('currency')
            ->whereUserId($user->id)
            ->get()
            ->sum('usdt_value');

        $OTCOrderCount = OTCOrder::where('user_id', $user->id)->count();
        $totalOTCOrderValue = OTCOrder::where('user_id', $user->id)
            ->sum(\DB::raw('quantity * price'));


        return view('dashboard.wallet.user-wallets', [
            'wallets' => $walletsWithAssetsValues,
            'user' => $user,
            'totalAssetsValue' => $totalAssetsValue,
            'totalAvailableAssetsValue' => $totalAvailableAssetsValue,
            'totalBlockedAssetsValue' => $totalBlockedAssetsValue,
            'totalDepositsValue' => $totalDepositsValue,
            'totalWithdrawalsValue' => $totalWithdrawalsValue,
            'OTCOrderCount' => $OTCOrderCount,
            'totalOTCOrderValue' => $totalOTCOrderValue,

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
             $deposits = Deposit::where('currency_symbol',$wallet->currency_symbol)->where('user_id', $user->id)->get();

            // Optionally create an empty collection for withdrawals:
            $withdrawals = collect([]);

        } else {
            $withdrawals = Withdrawal::where('currency_symbol', $wallet->currency_symbol)->where('user_id', $user->id)
                ->get();

            $deposits = collect([]);
        }

        // Fetch the total deposit amount and the last deposit date

        $totalDeposits = Deposit::whereStatus(DepositStatusEnum::CONFIRMED)->where('user_id', $user->id)
            ->where('currency_symbol', $wallet->currency_symbol)
            ->sum('amount');

        $totalDepositsValue = $this->depositService->totalDepositValueBasedCurrency($wallet->currency_symbol, $user->id);

        $lastDeposit = Transaction::whereIn('type', [TransactionTypeEnum::DEPOSIT])
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->first();

        $lastDepositDate = $lastDeposit ? \App\Helpers\DateFormatter::convertToPersianDate($lastDeposit->created_at, '%d %B %Y') : 'بدون واریزی';


        // Fetch the total withdraw amount and the last withdraw date
        $totalWithdraws = Withdrawal::where('currency_symbol', $wallet->currency_symbol)->where('user_id', $user->id)
            ->sum('amount');

        $totalWithdrawValue = $this->withdrawalService->totalWithdrawalValueBasedCurrency($wallet->currency_symbol, $user->id);

        $lastWithdraw = Transaction::where('type', TransactionTypeEnum::WITHDRAWAL)
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->first();

        $lastWithdrawDate = $lastWithdraw ? \App\Helpers\DateFormatter::convertToPersianDate($lastWithdraw->created_at, '%d %B %Y') : 'بدون برداشت';

        if ($user->id !== $this->exchangeUserId){
            $totalOtcSell = $this->otcService->totalOTCOrder($user->id, $wallet->currency_symbol,OTCOrderTypeEnum::SELL);
            $totalOtcSellValue = $this->otcService->totalOTCOrderValue($user->id, $wallet->currency_symbol, OTCOrderTypeEnum::SELL);

            $totalOtcBuy = $this->otcService->totalOTCOrder($user->id, $wallet->currency_symbol,OTCOrderTypeEnum::BUY);
            $totalOtcBuyValue = $this->otcService->totalOTCOrderValue($user->id, $wallet->currency_symbol,OTCOrderTypeEnum::BUY);

        }else{
//            TODO: calculate exchange OTC from Transaction
            $totalOtcSell = $this->transactionService->totalTransactionsBasedType($user->id, $wallet->currency_symbol,[TransactionTypeEnum::SELL]);
            $totalOtcSellValue = $this->transactionService->totalTransactionsValueBasedType($user->id, $wallet->currency_symbol,[TransactionTypeEnum::SELL]);

            $totalOtcBuy = $this->transactionService->totalTransactionsBasedType($user->id, $wallet->currency_symbol,[TransactionTypeEnum::BUY]);
            $totalOtcBuyValue = $this->transactionService->totalTransactionsValueBasedType($user->id, $wallet->currency_symbol,[TransactionTypeEnum::BUY]);
        }

        $lastOtcBuy = Transaction::where('type', 'buy')
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->first();

        $lastOtcBuyDate = $lastOtcBuy ? \App\Helpers\DateFormatter::convertToPersianDate($lastOtcBuy->created_at, '%d %B %Y') : 'بدون خرید OTC';

        $lastOtcSell = Transaction::where('type', TransactionTypeEnum::SELL->value)
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->first();
        $lastOtcSellDate = $lastOtcSell ? \App\Helpers\DateFormatter::convertToPersianDate($lastOtcSell->created_at, '%d %B %Y') : 'بدون فروش OTC';
        // Fetch the total OTC sell and buy amounts and last transaction dates

        return view('dashboard.wallet.wallet-transaction-details', [
            'user' => $user,
            'wallet' => $wallet,
            'specificAssetValue' => $specificAssetValue,
            'transactionTitle' => $transactionTitle,
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
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


}
