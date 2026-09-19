<?php

namespace App\Http\Controllers\User;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\StockContract;
use App\Models\User;
use App\Models\Withdrawal;
use App\Repositories\WalletRepository;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $user = null;

        if (str_starts_with($email, '#')) {
            $id = trim(ltrim($email, '#'));
            if (ctype_digit($id)) {
                $user = User::query()->find((int) $id);
            }
        }

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = User::query()
                ->where('email', 'LIKE', $email.'%')
                ->orderByRaw('email_verified_at IS NULL')
                ->first();
        }

        if (! $user) {
            $user = User::query()
                ->where('email', 'LIKE', '%'.$email.'%')
                ->orderByRaw('email_verified_at IS NULL')
                ->first();
        }
        if (!$user){
            Toast::message('کاربر با این شناسه یا ایمیل یافت نشد.')->warning()->notify();
            return redirect()->back()->withInput();
        }

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

        $stockContracts = StockContract::query()->whereUserId($user->id)->with(['stock', 'transactions'])->orderBy('created_at', 'desc')->take(5)->get();
        $totalStockOrdersCount = StockContract::query()->whereUserId($user->id)->count();

        $wallets = $user->wallets()->with(['walletChains', 'currency.chains'])->get();

        $totalAssetsValue = $this->walletService->totalAssetsValue($user);
        $totalAvailableAssetsValue = $this->walletService->totalAvailableAssetsValue($user);
        $totalBlockedAssetsValue = $this->walletService->totalBlockedAssetsValue($user);

        // Calculate yesterday's profit/loss
        $yesterdayProfitLoss = $this->walletService->calculateYesterdayProfitLoss($user);

        // Calculate the value of each wallet's currency
        $walletsWithAssetsValues = $wallets->map(function ($wallet) {
            $specificAssetValue = $this->walletService->specificAssetValue($wallet->user, $wallet->currency_symbol);
            $wallet->assetValue = $specificAssetValue; // Add the value to the wallet object
            return $wallet;
        })->sortByDesc('assetValue');

        $orphanWallets = $walletsWithAssetsValues->filter(fn ($wallet) => $wallet->currency === null);
        if ($orphanWallets->isNotEmpty()) {
            Log::warning('Orphan wallets found on user inquiry: currency_symbol missing from currencies table', [
                'user_id' => $user->id,
                'orphan_count' => $orphanWallets->count(),
                'orphans' => $orphanWallets->map(fn ($wallet) => [
                    'wallet_id' => $wallet->id,
                    'currency_symbol' => $wallet->currency_symbol,
                    'balance' => $wallet->balance,
                    'locked_balance' => $wallet->locked_balance,
                ])->values()->all(),
            ]);
        }

        // Get all currencies for wallet creation modal
        $currencies = Currency::orderBy('symbol')->get();

        return view('dashboard.inquiry_user.full-report', [
            'user' => $user,
            'otcOrders' => $otcOrders,
            'totalOtcOrdersCount' => $totalOtcOrdersCount,
            'withdraws' => $withdraws,
            'totalWithdrawsCount' => $totalWithdrawsCount,
            'deposits' => $deposits,
            'totalDepositsCount' => $totalDepositsCount,
            'stockContracts' => $stockContracts,
            'totalStockOrdersCount' => $totalStockOrdersCount,
            'walletsWithAssetsValues' => $walletsWithAssetsValues,
            'totalAssetsValue' => $totalAssetsValue,
            'totalAvailableAssetsValue' => $totalAvailableAssetsValue,
            'totalBlockedAssetsValue' => $totalBlockedAssetsValue,
            'yesterdayProfitLoss' => $yesterdayProfitLoss,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Create a new wallet for a user
     */
    public function createWallet(Request $request, WalletRepository $walletRepository)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'currency_symbol' => 'required|exists:currencies,symbol',
        ]);

        try {
            $userId = $request->user_id;
            $currencySymbol = $request->currency_symbol;

            // Check if wallet already exists
            $existingWallet = \App\Models\Wallet::where('user_id', $userId)
                ->where('currency_symbol', $currencySymbol)
                ->first();

            if ($existingWallet) {
                Toast::message('این کیف پول قبلاً ایجاد شده است.')->warning()->notify();
                return redirect()->back();
            }

            // Create wallet using WalletRepository
            $wallet = $walletRepository->getOrCreateWallet($userId, $currencySymbol);

            if ($wallet) {
                Toast::message('کیف پول با موفقیت ایجاد شد. اکنون می‌توانید آدرس‌های شبکه را برای آن ایجاد کنید.')->success()->notify();
            } else {
                Toast::message('خطا در ایجاد کیف پول.')->danger()->notify();
            }
        } catch (\Exception $e) {
            Toast::message('خطا: ' . $e->getMessage())->danger()->notify();
        }

        return redirect()->back();
    }
}
