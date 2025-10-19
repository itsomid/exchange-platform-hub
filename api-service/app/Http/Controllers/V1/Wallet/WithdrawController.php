<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Enums\WithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\WithdrawRequest;
use App\Http\Resources\V1\Wallet\WithdrawalResource;
use App\Http\Resources\V1\Wallet\WithdrawalListResource;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WithdrawController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
        private readonly WithdrawalRepositoryInterface $withdrawalRepository
    ) {}

    public function lists(Request $request)
    {
        $userId = Auth::id();
        $currency = $request->query('currency');
        $limit = min((int) $request->query('limit', 5), 50); // Default 5, max 50
        $page = max((int) $request->query('page', 1), 1); // Default 1, min 1

        // Use repository to get withdrawals
        $withdrawals = $this->withdrawalRepository->getWithdrawals($userId, $currency);

        // Manual pagination since repository returns Collection
        $total = $withdrawals->count();
        $offset = ($page - 1) * $limit;
        $paginatedWithdrawals = $withdrawals->slice($offset, $limit)->values();

        $lastPage = (int) ceil($total / $limit);
        $from = $total > 0 ? $offset + 1 : null;
        $to = $total > 0 ? min($offset + $limit, $total) : null;

        return response()->json([
            'data' => WithdrawalListResource::collection($paginatedWithdrawals),
            'meta' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ]
        ]);
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
