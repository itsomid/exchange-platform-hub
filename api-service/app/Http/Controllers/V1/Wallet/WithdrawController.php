<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\WithdrawRequest;
use App\Http\Resources\V1\Withdrawal\WithdrawalResource;
use App\Http\Resources\V1\Withdrawal\WithdrawalListCollection;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Withdrawals",
 *     description="Withdrawal management endpoints"
 * )
 */
class WithdrawController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
        private readonly WithdrawalRepositoryInterface $withdrawalRepository
    ) {}

    public function lists(Request $request)
    {

        $userId = Auth::id();
        $currencySymbol = $request->query('currency');
        $status = $request->query('status');
        $page =  $request->query('page', 1);
        $perPage = $request->query('limit', 10);
        $withdrawals = $this->withdrawalRepository->getWithdrawalsPaginated(
            $userId,
            $currencySymbol,
            $status,
            $page,
            $perPage
        );

        return new WithdrawalListCollection($withdrawals);
    }


    public function __invoke(WithdrawRequest $request)
    {
        $validatedData = $request->validated();
        $withdrawResponse = $this->withdrawalService->createWithdrawal(
            resolve(CreateWithdrawalRequestDTO::class)
                ->setUserId(Auth::id())
                ->setCurrencySymbol($validatedData['currency'])
                ->setCurrencyChain($validatedData['currency_chain'])
                ->setAmount($validatedData['amount'])
                ->setAddress($validatedData['destination_address'])
                ->setRemark($validatedData['remark'] ?? null)
        );

        return new WithdrawalResource($withdrawResponse);
    }


    public function checkWithdrawalLimit()
    {
        $service = resolve(\App\Services\User\FinancialBlockService::class);
        $blockState = $service->getUserBlockedState(Auth::id(), \App\Enums\FinancialBlockActionEnum::WITHDRAW);

        return response()->json([
            'is_blocked' => $blockState->isBlock(),
            'restrict_until' => $blockState->isBlock() ? $blockState->getRestrictUntil()->toDateTimeString() : null,
        ]);
    }
}
