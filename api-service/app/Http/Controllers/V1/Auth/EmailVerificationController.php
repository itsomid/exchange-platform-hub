<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\EmailVerificationRequest;
use App\Http\Resources\V1\Profile\UserProfileResource;
use App\Services\Auth\DTO\EmailVerifyRequestDTO;
use App\Services\Auth\EmailVerificationService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
        private readonly WalletService $walletService,
    ) {}


    public function __invoke(EmailVerificationRequest $request)
    {
        $validatedData = $request->validated();

        $this->emailVerificationService->verify(
            resolve(EmailVerifyRequestDTO::class)
                ->setUserId(Auth::id())
                ->setToken($validatedData['token'])
        );
        //Create USDT Wallet
        $this->walletService->createWallet(Auth::id(), 'USDT');

        return response([
            'message' => __('auth.email-verification.success'),
            'data' => new UserProfileResource(Auth::user()),
        ]);
    }
}
