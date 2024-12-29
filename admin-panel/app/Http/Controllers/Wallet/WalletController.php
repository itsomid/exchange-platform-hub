<?php

namespace App\Http\Controllers\Wallet;

use App\Enums\TransactionTypeEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\IncreaseCreditRequest;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Transaction\TransactionService;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function increaseCreditForm(Request $request)
    {

        $currencies = Currency::all();

        $selectedUser = User::find($request->user);
        $selectedCurrency = $request->get('currency');
        return view('dashboard.wallet.increase-credit', [
            'currencies' => $currencies,
            'selectedUser' => $selectedUser,
            'selectedCurrency' => $selectedCurrency,
        ]);
    }

    public function increaseCredit(IncreaseCreditRequest $request, TransactionService $transactionService)
    {

        try {
            $admin = auth()->user(); // Assuming the admin is logged in.


            // Check if the wallet for the specified currency exists
             $fromUserId = $request->transaction_type === TransactionTypeEnum::WITHDRAWAL->value ? $request->user : TransactionService::EXCHANGE_USER_ID;
             $toUserId = $request->transaction_type === TransactionTypeEnum::DEPOSIT->value ? $request->user : TransactionService::EXCHANGE_USER_ID;

            $transactionService->transferBetweenWallets(
                fromUserId: $fromUserId,
                toUserId: $toUserId,
                amount: $request->amount,
                currency: $request->currency,
                type: $request->transaction_type,
                subtype: 'manual_admin',
                adminId: Auth::user()->id,
                description: $request->description ?? 'Manual transaction by admin #' . $admin->id,
                admin_description: $request->admin_description
            );
            Toast::message('.افزایش اعتبار با موفقیت انجام شد')->success()->notify();
            return redirect()->back();

        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->with('error', 'An error occurred while updating the credit. Please try again.');
        }

    }
    public function blockBalanceForm(Wallet $wallet, User $user)
    {

        return view('dashboard.wallet.block-balance', [
            'wallet'  => $wallet,
            'user'  => $user,
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

        return redirect()->back()->with('success', 'موجودی کاربر با موفقیت بروزسانی شد.');
    }
    public function unblockBalanceForm(Wallet $wallet, User $user)
    {

        return view('dashboard.wallet.unblock-balance', [
            'wallet'  => $wallet,
            'user'  => $user,
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

        return redirect()->back()->with('success', 'موجودی کاربر با موفقیت بروزسانی شد.');
    }
}
