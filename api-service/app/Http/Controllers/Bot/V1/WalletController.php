<?php

namespace App\Http\Controllers\Bot\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\V1\TransferInRequest;
use App\Http\Requests\Bot\V1\TransferOutRequest;
use App\Http\Resources\Bot\V1\BotWalletResource;
use App\Services\Bot\BotWalletService;
use App\Services\Bot\FeeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function __construct(
        private readonly BotWalletService $botWalletService,
        private readonly FeeCalculator $feeCalculator,
    ) {}

    public function show(): JsonResponse
    {
        $wallet = $this->botWalletService->getOrCreateBotWallet(Auth::user());

        return response()->json(['data' => new BotWalletResource($wallet)]);
    }

    public function transferIn(TransferInRequest $request): JsonResponse
    {
        $amount = (string) $request->validated('amount');
        $this->botWalletService->transferIn(Auth::user(), $amount);

        return response()->json(['message' => 'انتقال به ربات با موفقیت انجام شد.']);
    }

    public function transferOut(TransferOutRequest $request): JsonResponse
    {
        $amount = (string) $request->validated('amount');
        $this->botWalletService->transferOut(Auth::user(), $amount);

        return response()->json(['message' => 'برداشت از ربات با موفقیت انجام شد.']);
    }

    public function fee(TransferInRequest $request): JsonResponse
    {
        $amount = (string) $request->validated('amount');
        $fee    = $this->feeCalculator->transferFee($amount);

        return response()->json(['data' => ['fee' => $fee]]);
    }
}
