<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ExchangePrice;
use App\Models\Market;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;

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

        $totalAssetsValue = $this->walletService->totalAssets($user);
        return view('dashboard.user.wallets.user-wallets', [
            'wallets' => $wallets,
            'user' => $user,
            'totalAssetsValue' => $totalAssetsValue
        ]);
    }

}
