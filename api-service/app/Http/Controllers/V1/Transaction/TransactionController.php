<?php

namespace App\Http\Controllers\V1\Transaction;

use App\Http\Controllers\Controller;
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
    public function allDepositWithdraw()
    {
        $depositWithdrawList = $this->transactionService->getAllDepositWithdraw(
            resolve(GetAllDepositWithdrawRequestDTO::class)
                ->setUserId(Auth::id())
        );

        return new DepositWithdrawCollection($depositWithdrawList);
    }
}
