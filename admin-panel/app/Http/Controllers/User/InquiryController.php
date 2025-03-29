<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Wallet\WalletService;

class InquiryController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService,)
    {
        $this->walletService = $walletService;
    }

    public function index()
    {
        return view('dashboard.inquiry_user.index');
    }

    public function submit()
    {
        request()->validate([
            'email' => ['required'],
        ]);
        $email = request()->input('email');
        $user = User::query()->where('email', 'LIKE', '%' . $email . '%')->first();

        return redirect()->route('admin.inquiry.user-details', ['user' => $user]);
    }

    public function userDetails(User $user)
    {
        $otcOrders = OTCOrder::query()->whereUserId($user->id)->with(['market', 'transactions'])->orderBy('created_at', 'desc')->take(5)->get();
        $totalOtcOrdersCount = OTCOrder::query()->whereUserId($user->id)->count();

        $withdraws = Withdrawal::query()->whereUserId($user->id)->with(['user', 'currency', 'transaction'])->orderBy('created_at', 'desc')->take(5)->get();
        $totalWithdrawsCount = Withdrawal::query()->whereUserId($user->id)->count();

        $deposits = Deposit::query()->whereUserId($user->id)->with(['user', 'currency', 'transaction'])->orderBy('created_at', 'desc')->take(5)->get();
        $totalDepositsCount = Deposit::query()->whereUserId($user->id)->count();

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

        return view('dashboard.inquiry_user.full-report', [
            'user' => $user,
            'otcOrders' => $otcOrders,
            'totalOtcOrdersCount' => $totalOtcOrdersCount,
            'withdraws' => $withdraws,
            'totalWithdrawsCount' => $totalWithdrawsCount,
            'deposits' => $deposits,
            'totalDepositsCount' => $totalDepositsCount,
            'walletsWithAssetsValues' => $walletsWithAssetsValues,
            'totalAssetsValue' => $totalAssetsValue,
            'totalAvailableAssetsValue' => $totalAvailableAssetsValue,
            'totalBlockedAssetsValue' => $totalBlockedAssetsValue,
        ]);
    }
}
