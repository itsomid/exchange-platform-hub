<?php

namespace App\Http\Controllers\Wallet;

use App\Enums\LockedBalanceTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\IncreaseCreditRequest;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Services\Transaction\TransactionService;
use App\Services\Wallet\CheckWalletService;
use App\Services\Wallet\DTO\CheckWallet\CheckUserDepositRequestDTO;
use App\Services\Wallet\WalletService;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class WalletController extends Controller
{
    protected $bitexroomUserId;

    public function __construct()
    {

        $this->bitexroomUserId = config('bitexroom.user_id', 1);
    }

    public function increaseCreditForm(Request $request)
    {
        $data = $this->getCreditFormData($request);
        return view('dashboard.wallet.increase-credit', $data);
    }

    public function decreaseCreditForm(Request $request)
    {
        $data = $this->getCreditFormData($request);
        return view('dashboard.wallet.decrease-credit', $data);
    }

    private function getCreditFormData(Request $request)
    {
        // Initialize variables to avoid undefined variable warnings
        $currencies = Currency::all();
        $currencyChains = CurrencyChain::all();
        $selectedCurrency = null;
        $selectedUser = null;

        // Check if a specific currency is selected
        if ($request->has('currency')) {
            $selectedCurrency = Currency::where('symbol', $request->get('currency'))->first();
            if ($selectedCurrency) {
                $currencyChains = $selectedCurrency->chains;
            }
        }

        // Check if a specific user is selected
        if ($request->has('user')) {
            $selectedUser = User::find($request->user);
        }

        return [
            'currencies' => $currencies,
            'selectedUser' => $selectedUser,
            'selectedCurrency' => $selectedCurrency,
            'currencyChains' => $currencyChains,
        ];
    }

    public function increaseCredit(IncreaseCreditRequest $request, TransactionService $transactionService)
    {

        try {
            $admin = auth()->user(); // Assuming the admin is logged in.

            $currency = Currency::where('symbol', $request->currency)->first();
            $currencyChain = CurrencyChain::where('chain', $request->chain)->first();

            if (!$currency) {
                return redirect()->back()->withErrors(['currency' => 'ارز انتخاب شده معتبر نیست.']);
            }

            // Retrieve valid chains for this currency
            $validChains = $currency->chains()->pluck('chain')->map(fn($chain) => $chain->value)->toArray();
            // Check if the selected chain is valid
            if (!in_array($request->chain, $validChains)) {
                return redirect()->back()->withErrors(['chain' => 'شبکه انتخاب شده با ارز مطابقت ندارد.']);
            }


            if ($request->user == $this->bitexroomUserId) {

                $transactionService->increaseDecreaseAdminWalletCredit(
                    userId: $this->bitexroomUserId,
                    amount: $request->amount,
                    currency: $currency,
                    currencyChain: $currencyChain,
                    type: TransactionTypeEnum::DEPOSIT->value,
                    transactionHash: $request->transaction_hash, // Always increasing
                    adminId: $admin->id,
                    description: 'Manual credit increase by admin #' . $admin->id,
                    admin_description: $request->admin_description
                );

                Toast::message('واریز اعتبار با موفقیت انجام شد.')->success()->notify();
                return redirect()->route('admin.wallet.index', ['user' => $this->bitexroomUserId]);
            } else {

                $transactionService->transferBetweenWallets(
                    fromUserId: $this->bitexroomUserId,
                    toUserId: $request->user,
                    amount: $request->amount,
                    transactionHash: $request->transaction_hash,
                    currency: $currency,
                    currencyChain: $currencyChain,
                    type: TransactionTypeEnum::DEPOSIT->value,
                    adminId: Auth::user()->id,
                    description: 'Manual transfer by admin #' . $admin->id,
                    admin_description: $request->admin_description
                );

                Toast::message('واریز اعتبار با موفقیت انجام شد.')->success()->notify();
                return redirect()->route('admin.wallet.index', ['user' => $request->user]);
            }
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withErrors(['general' => $exception->getMessage()]);
        }
    }

    public function decreaseCredit(IncreaseCreditRequest $request, TransactionService $transactionService)
    {

        try {
            $admin = auth()->user(); // Assuming the admin is logged in.

            $currency = Currency::where('symbol', $request->currency)->first();
            $currencyChain = CurrencyChain::where('chain', $request->chain)->first();

            if (!$currency) {
                return redirect()->back()->withErrors(['currency' => 'ارز انتخاب شده معتبر نیست.']);
            }

            // Retrieve valid chains for this currency
            $validChains = $currency->chains()->pluck('chain')->map(fn($chain) => $chain->value)->toArray();
            // Check if the selected chain is valid
            if (!in_array($request->chain, $validChains)) {
                return redirect()->back()->withErrors(['chain' => 'شبکه انتخاب شده با ارز مطابقت ندارد.']);
            }


            if ($request->user == $this->bitexroomUserId) {

                $transactionService->increaseDecreaseAdminWalletCredit(
                    userId: $this->bitexroomUserId,
                    amount: $request->amount,
                    currency: $currency,
                    currencyChain: $currencyChain,
                    type: TransactionTypeEnum::WITHDRAWAL->value,
                    transactionHash: $request->transaction_hash,
                    adminId: $admin->id,
                    description: 'Manual credit increase by admin #' . $admin->id,
                    admin_description: $request->admin_description
                );

                Toast::message('برداشت اعتبار با موفقیت انجام شد.')->success()->notify();
                return redirect()->route('admin.wallet.index', ['user' => $this->bitexroomUserId]);
            } else {
                // Check if the wallet for the specified currency exists
                $fromUserId = $request->user;
                $toUserId = $this->bitexroomUserId;

                $transactionService->transferBetweenWallets(
                    fromUserId: $request->user,
                    toUserId: $this->bitexroomUserId,
                    amount: $request->amount,
                    transactionHash: $request->transaction_hash,
                    currency: $currency,
                    currencyChain: $currencyChain,
                    type: TransactionTypeEnum::WITHDRAWAL->value,
                    adminId: Auth::user()->id,
                    description: 'Manual transfer by admin #' . $admin->id,
                    admin_description: $request->admin_description
                );
                Toast::message('برداشت اعتبار با موفقیت انجام شد.')->success()->notify();
                return redirect()->route('admin.wallet.index', ['user' => $request->user]);
            }
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withErrors(['general' => $exception->getMessage()]);
        }
    }

    public function blockBalanceForm(Wallet $wallet, User $user)
    {
        $lockedBalanceDetails = $wallet->lockedBalanceDetails()->withTrashed()->orderBy('created_at', 'desc')->get();
        return view('dashboard.wallet.block-balance', [
            'wallet' => $wallet,
            'user' => $user,
            'lockedBalanceDetails' => $lockedBalanceDetails,
        ]);
    }

    public function blockBalance(Wallet $wallet, Request $request)
    {
        $validated = $request->validate([
            'block_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);
        if ($validated['block_amount'] == 0) {
            return redirect()->back()->withErrors(['block_amount' => 'مقدار بلاک باید بیشتر از 0 باشد']);
        }
        // Check if block amount is valid
        if ($validated['block_amount'] > $wallet->balance - $wallet->locked_balance) {
            return redirect()->back()->withErrors(['block_amount' => 'مقدار بلاکی از موجودی کاربر بیشتر است']);
        }

        // Update the blocked balance
        $wallet->locked_balance += $validated['block_amount'];
        $wallet->save();

        // Create locked balance detail record
        $wallet->lockedBalanceDetails()->create([
            'amount' => $validated['block_amount'],
            'type' => LockedBalanceTypeEnum::ADMIN,
            'admin_id' => auth()->id(),
            'description' => $validated['description'] ?? 'مسدودسازی موجودی از طرف ادمین #' . auth()->id() . ' (' . auth()->user()->fullname() . ')'
        ]);

        return redirect()->back()->with('success', 'موجودی کاربر با موفقیت بروزسانی شد.');
    }

    public function unblockBalanceForm(Wallet $wallet, User $user)
    {

        $lockedBalanceDetails = $wallet->lockedBalanceDetails()->withTrashed()->orderBy('created_at', 'desc')->get();

        return view('dashboard.wallet.unblock-balance', [
            'wallet' => $wallet,
            'user' => $user,
            'lockedBalanceDetails' => $lockedBalanceDetails,
        ]);
    }

    public function unblockBalance(Wallet $wallet, Request $request)
    {
        $validated = $request->validate([
            'unblock_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Check if unblock amount is valid
        if ($validated['unblock_amount'] > $wallet->locked_balance) {
            return redirect()->back()->withErrors(['unblock_amount' => 'مقدار آزادسازی موجودی از مقدار بلاک شده بیشتر است.']);
        }

        // Update the blocked balance
        $wallet->locked_balance -= $validated['unblock_amount'];
        $wallet->save();

        // Create locked balance detail record for unblock
        $wallet->lockedBalanceDetails()->create([
            'amount' => -$validated['unblock_amount'], // Negative amount to indicate unblock
            'type' => LockedBalanceTypeEnum::ADMIN,
            'admin_id' => auth()->id(),
            'description' => $validated['description'] ?? 'آزادسازی موجودی از طرف ادمین #' . auth()->id() . ' (' . auth()->user()->fullname() . ')'
        ]);

        return redirect()->back()->with('success', 'موجودی کاربر با موفقیت بروزسانی شد.');
    }

    public function updateExchangeWalletChain(WalletChain $walletChain, Request $request)
    {
        $walletChain->address = $request->address;
        $walletChain->save();

        Toast::message('آدرس با موفقیت به روز شد.')->success()->notify();
        return redirect()->back();
    }

    public function generateAddressFromHdWallet(Request $request, WalletService $walletService)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'currency' => 'required|string|exists:currencies,symbol',
                'chain' => 'required|string|exists:currency_chains,chain'
            ]);
            $response = $walletService->generateAddress(
                $request->user_id,
                $request->currency,
                $request->chain
            );
            Toast::message('آدرس با موفقیت تولید شد.')->success()->notify();
            return response()->json([
                'success' => true,
                'address' => $response,
                'message' => 'آدرس با موفقیت تولید شد.'
            ]);
        } catch (InternalWalletHasProblemException $e) {
            \Log::channel('hd-wallet')->error('Internal wallet problem:', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            Toast::message('خطا در تولید آدرس. لطفا دوباره تلاش کنید.')->danger()->notify();
            return response()->json([
                'success' => false,
                'message' => 'خطا در تولید آدرس. لطفا دوباره تلاش کنید.'
            ], 500);
        } catch (\Throwable $e) {

            Log::channel('hd-wallet')->error('Unexpected error in generateAddressFromHdWallet:', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            Toast::message('خطای سیستمی رخ داده است.')->danger()->notify();
            return response()->json([
                'success' => false,
                'message' => 'خطای سیستمی رخ داده است.'
            ], 500);
        }
    }

    public function createExchangeWalletChain(Wallet $wallet, $chainName, Request $request)
    {
        $walletChains = $wallet->walletChains()->create([
            'address' => $request->address,
            'currency_chain' => $chainName,
        ]);
        Toast::message('آدرس با موفقیت به روز شد.')->success()->notify();
        return redirect()->back();
    }

    public function refresh(User $user, Wallet $wallet)
    {
        try {
            $hasNewTransaction = resolve(CheckWalletService::class)
                ->checkUserDeposit(
                    resolve(CheckUserDepositRequestDTO::class)
                        ->setUserId($user->id)
                        ->setCurrencySymbol($wallet->currency_symbol)
                );

            if ($hasNewTransaction) {
                $transactionCount = session('deposit_transaction_count', 0);
                $totalAmount = session('total_deposit_amount', 0);
                $currencySymbol = session('currency_symbol', '');

                Toast::message('واریزی جدید برای کاربر یافت شد')->success()->notify();

                // پاک کردن اطلاعات سشن
                session()->forget(['deposit_transaction_count', 'total_deposit_amount', 'currency_symbol']);

                return redirect()->back()->with('success', "واریزی های جدید با موفقیت به حساب کاربر اعمال شد. تعداد واریز: {$transactionCount}، مجموع واریزی: {$totalAmount} {$currencySymbol}");
            } else {
                // Only show "no new deposits" message if there's no existing toast message
                if (!session()->has('toast')) {
                    Toast::message('واریزی جدیدی یافت نشد.')->info()->notify();
                }
            }
        } catch (\App\Exceptions\V1\Wallet\InternalWalletHasProblemException $exception) {
            Toast::message('سرویس کیف پول در دسترس نیست. لطفاً بعداً تلاش کنید.')->danger()->notify();
        } catch (\Throwable $exception) {
            report($exception);
            Toast::message('خطایی در چک کردن واریز رخ داد: ' . $exception->getMessage())->danger()->notify();
        }

        return redirect()->back();
    }

    /**
     * Refresh wallet balance for a specific chain
     */
    public function refreshByChain(User $user, $walletChainId)
    {
        try {
            $hasNewTransaction = resolve(CheckWalletService::class)
                ->checkUserDepositByChain($user->id, $walletChainId);
            
            if ($hasNewTransaction) {
                $transactionCount = session('deposit_transaction_count', 0);
                $totalAmount = session('total_deposit_amount', 0);
                $currencySymbol = session('currency_symbol', '');
                $chainName = session('chain_name', '');
                
                Toast::message("واریزی جدید در شبکه {$chainName} برای کاربر یافت شد")->success()->notify();
                
                // پاک کردن اطلاعات سشن
                session()->forget(['deposit_transaction_count', 'total_deposit_amount', 'currency_symbol', 'chain_name']);
                
                return redirect()->back()->with('success', "واریزی های جدید با موفقیت به حساب کاربر اعمال شد. تعداد واریز: {$transactionCount}، مجموع واریزی: {$totalAmount} {$currencySymbol}");
            } else {
                if (!session()->has('toast')) {
                    Toast::message('واریزی جدیدی یافت نشد.')->info()->notify();
                }
            }
        } catch (\App\Exceptions\V1\Wallet\InternalWalletHasProblemException $exception) {
            Toast::message('سرویس کیف پول در دسترس نیست. لطفاً بعداً تلاش کنید.')->danger()->notify();
        } catch (\Throwable $exception) {
            report($exception);
            Toast::message('خطایی در چک کردن واریز رخ داد: ' . $exception->getMessage())->danger()->notify();
        }
        
        return redirect()->back();
    }

    /**
     * Create wallet chains for all available chains of a currency
     */
    public function createWalletChains(Request $request, WalletService $walletService)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'currency_symbol' => 'required|string|exists:currencies,symbol'
        ]);

        $result = $walletService->createWalletChainsForCurrency(
            $request->user_id,
            $request->currency_symbol
        );

        if ($result['success']) {
            $createdCount = count($result['created_chains']);
            if ($createdCount > 0) {
                Toast::message("با موفقیت {$createdCount} زنجیره کیف پول ایجاد شد.")->success()->notify();
            } else {
                Toast::message('تمام زنجیره‌های کیف پول از قبل موجود بودند.')->info()->notify();
            }
        } else {
            Toast::message('خطا در ایجاد زنجیره‌های کیف پول: ' . $result['message'])->danger()->notify();
        }

        return redirect()->back();
    }
}
