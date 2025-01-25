<?php

namespace App\Http\Controllers\V1\Transaction;

use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Transaction\AllDepositWithdrawalRequest;
use App\Http\Resources\V1\Transaction\DepositWithdrawCollection;
use App\Services\Transaction\DTO\GetAllDepositWithdrawRequestDTO;
use App\Services\Transaction\TransactionService;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/all-deposit-withdraw",
     *     summary="Get all deposit and withdrawal transactions",
     *     description="Retrieve a list of all deposit and withdrawal transactions for the authenticated user.",
     *     tags={"Transactions"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *        name="ccy",
     *        in="query",
     *        required=false,
     *        description="The currency symbol for filtering transactions.",
     *
     *        @OA\Schema(type="string", example="BTC")
     *    ),
     *
     *    @OA\Parameter(
     *        name="transaction_type",
     *        in="query",
     *        required=false,
     *        description="The type of transaction to filter (e.g., deposit, withdrawal).",
     *
     *        @OA\Schema(type="string", enum={"deposit", "withdrawal"}, example="deposit")
     *    ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of deposit and withdrawal transactions.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/DepositWithdrawResource")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, user is not authenticated.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function allDepositWithdraw(AllDepositWithdrawalRequest $request)
    {
        $transactionType = null;
        if ($request->has('transaction_type')) {
            $transactionType = TransactionTypeEnum::from($request->input('transaction_type'));
        }
        $depositWithdrawList = $this->transactionService->getAllDepositWithdraw(
            resolve(GetAllDepositWithdrawRequestDTO::class)
                ->setUserId(Auth::id())
                ->setTransactionType($transactionType)
                ->setCurrencySymbol($request->input('ccy'))
        );

        return new DepositWithdrawCollection($depositWithdrawList);
    }
}
