<?php

namespace App\Http\Controllers\Wallet;

use App\DTO\StudentAccount\ChargeAccountDTO;
use App\Enums\BalanceOperationEnum;
use App\Enums\DepositTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\IncreaseCreditRequest;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function increaseCreditForm()
    {

        $currencies = Currency::all();
        return view('dashboard.wallet.increase-credit', [
            'currencies' => $currencies
        ]);
    }

    public function increaseCredit(IncreaseCreditRequest $request, WalletService $walletService)
    {

        try {
            $admin = auth()->user(); // Assuming the admin is logged in.

            DB::transaction(function () use ($request, $admin, $walletService) {

                // Check if the wallet for the specified currency exists
                $wallet = Wallet::firstOrCreate(
                    [
                        'user_id' => $request->user,
                        'currency_symbol' => $request->currency, // Use currency to find/create wallet
                    ],
                    [
                        'balance' => 0, // Initialize balance for new wallet
                    ]
                );

                // Validate transaction type
                if (!in_array($request->transaction_type, [TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::WITHDRAWAL->value])) {
                    throw new \InvalidArgumentException('Invalid transaction type.');
                }

                if ($request->transaction_type === TransactionTypeEnum::WITHDRAWAL->value && $wallet->balance < $request->amount) {
                    throw new \Exception('Insufficient balance for withdrawal.');
                }

                // Calculate the new balance
                // Calculate the new balance
                $newBalance = $request->transaction_type === TransactionTypeEnum::DEPOSIT->value
                    ? $wallet->balance + $request->amount
                    : $wallet->balance - $request->amount;

                // Log the transaction
                Transaction::create([
                    'user_id' => $request->user,
                    'wallet_id' => $wallet->id,
                    'admin_id' => $admin->id,
                    'amount' => $request->amount,
                    'balance' => $newBalance,
                    'type' => $request->transaction_type,
                    'status' => 'completed',
                    'description' => $request->description ?? 'Credit increased by admin #' . $admin->id,
                ]);
                // Update the wallet balance using WalletService.

                $walletService->updateBalance(
                    resolve(UpdateBalanceRequestDTO::class)
                        ->setAmount($request->amount)
                        ->setOperation($request->transaction_type === TransactionTypeEnum::DEPOSIT->value? BalanceOperationEnum::INCREASE : BalanceOperationEnum::DECREASE)
                        ->setCurrencySymbol($request->currency)
                        ->setUserId($request->user)
                );


            });
        }catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->with('error', 'An error occurred while updating the credit. Please try again.');
        }

        Toast::message('.افزایش اعتبار با موفقیت انجام شد')->success()->notify();
        return redirect()->back();
    }
}
