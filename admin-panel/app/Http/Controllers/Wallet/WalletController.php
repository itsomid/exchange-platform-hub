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
use App\Services\Transaction\TransactionService;
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
}
