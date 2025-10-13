<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Enums\WithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\WithdrawRequest;
use App\Http\Resources\V1\Wallet\CheckWithdrawalResource;
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


    public function checkWithdrawal()
    {
        $response = $this->withdrawalService->checkSpecificUserWithdrawal(Auth::id());

        if ($response->getStatus() === null) {
            return response([], Response::HTTP_NO_CONTENT);
        }

        $message = __('messages.withdrawals.check_withdrawal_started');
        if ($response->getStatus() === WithdrawalStatusEnum::COMPLETED) {
            $message = __('messages.withdrawals.check_withdrawal_success');
        } elseif ($response->getStatus() === WithdrawalStatusEnum::FAILED) {
            $message = __('messages.withdrawals.check_withdrawal_error');
        }

        $data = [
            'available_in' => now()->addMinutes(config('bitexroom.withdrawal.check_wallet_attempts'))->format('Y-m-d H:i:s'),
            'has_new_transaction' => $response->getStatus() === WithdrawalStatusEnum::COMPLETED || $response->getStatus() === WithdrawalStatusEnum::FAILED,
        ];
        $data['withdrawal_details'] = new CheckWithdrawalResource($response);

        return response([
            'message' => $message,
            'status' => $response->getStatus(),
            'data' => $data,
        ]);
    }


    public function checkWithdrawalById(int $withdrawalId)
    {
        // Find the withdrawal record for the authenticated user
        $withdrawal = \App\Models\Withdrawal::query()
            ->with('currencyChain', 'user')
            ->where('id', $withdrawalId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$withdrawal) {
            return response([
                'message' => __('messages.withdrawals.withdrawal_not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if withdrawal is in pending status
        if ($withdrawal->status !== WithdrawalStatusEnum::PENDING) {
            return response([
                'message' => __('messages.withdrawals.withdrawal_not_pending'),
                'status' => $withdrawal->status,
                'data' => [
                    'withdrawal_id' => $withdrawal->id,
                    'withdrawal_details' => new CheckWithdrawalResource($withdrawal),
                ],
            ]);
        }

        // Use the existing withdrawal service to check the specific withdrawal
        $service = resolve(WithdrawalService::class);
        $response = $service->checkWithdrawal(collect([$withdrawal]));

        if ($response->getStatus() === null) {
            return response([
                'message' => __('messages.withdrawals.check_deposit_no_update'),
                'data' => [
                    'withdrawal_id' => $withdrawalId,
                    'has_new_transaction' => false,
                ],
            ], Response::HTTP_NO_CONTENT);
        }

        $message = __('messages.withdrawals.check_deposit_started');
        if ($response->getStatus() === WithdrawalStatusEnum::COMPLETED) {
            $message = __('messages.withdrawals.check_deposit_success');
        } elseif ($response->getStatus() === WithdrawalStatusEnum::FAILED) {
            $message = __('messages.withdrawals.check_deposit_error');
        }

        $data = [
            'withdrawal_id' => $withdrawalId,
            'has_new_transaction' => $response->getStatus() === WithdrawalStatusEnum::COMPLETED || $response->getStatus() === WithdrawalStatusEnum::FAILED,
        ];
        $data['withdrawal_details'] = new CheckWithdrawalResource($response);

        return response([
            'message' => $message,
            'status' => $response->getStatus(),
            'data' => $data,
        ]);
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
