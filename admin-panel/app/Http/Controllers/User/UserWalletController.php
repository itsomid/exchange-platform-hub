<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
            'withdraw' => ['title' => 'برداشت', 'data' => $wallet],
            'otc-buy' => ['title' => 'خرید OTC', 'data' => $wallet],
            'otc-sell' => ['title' => 'فروش OTC', 'data' => $wallet],
        ];
        $transactionTitle = $transactionTypes[$type]['title'] ?? 'Transactions';
        //TODO: deposit Transaction
        return view('dashboard.wallet.wallet-transaction-details', [
            'user' => $user,
            'wallet' => $wallet,
            'specificAssetValue' => $specificAssetValue,
            'transactionTitle' => $transactionTitle
        ]);
    }

    public function blockAssetBalance()
    {

    }

}
