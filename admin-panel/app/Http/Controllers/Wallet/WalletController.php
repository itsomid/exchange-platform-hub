<?php

namespace App\Http\Controllers\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Enums\LockedBalanceTypeEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\IncreaseCreditRequest;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Services\Transaction\TransactionService;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    protected $exchangeUserId;

    public function __construct()
    {

        $this->exchangeUserId = config('exchange.exchange_user_id', 1);
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


            if ($request->user == $this->exchangeUserId) {

                $transactionService->increaseDecreaseAdminWalletCredit(
                    userId: $this->exchangeUserId,
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
                return redirect()->route('admin.wallet.index', ['user' => $this->exchangeUserId]);

            } else {

                $transactionService->transferBetweenWallets(
                    fromUserId: $this->exchangeUserId,
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


            if ($request->user == $this->exchangeUserId) {

                $transactionService->increaseDecreaseAdminWalletCredit(
                    userId: $this->exchangeUserId,
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
                return redirect()->route('admin.wallet.index', ['user' => $this->exchangeUserId]);

            } else {
                // Check if the wallet for the specified currency exists
                $fromUserId = $request->user;
                $toUserId = $this->exchangeUserId;

                $transactionService->transferBetweenWallets(
                    fromUserId: $request->user,
                    toUserId: $this->exchangeUserId,
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

    public function createExchangeWalletChain(Wallet $wallet, $chainName, Request $request)
    {
        $walletChains = $wallet->walletChains()->create([
            'address' => $request->address,
            'currency_chain' => $chainName,
        ]);
        Toast::message('آدرس با موفقیت به روز شد.')->success()->notify();
        return redirect()->back();

    }
}
