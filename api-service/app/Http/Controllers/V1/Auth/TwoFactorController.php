<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\DisableTwoFactorRequest;
use App\Http\Requests\V1\Auth\SaveSecretRequest;
use App\Http\Requests\V1\Auth\ValidateTwoFactorRequest;
use App\Http\Resources\Auth\AccessTokenResource;
use App\Http\Resources\TwoFactorSetupResource;
use App\Services\Auth\AccessTokenService;
use App\Services\Auth\DTO\CheckTwoFactorRequestDTO;
use App\Services\Auth\DTO\DisableTwoFactorRequestDTO;
use App\Services\Auth\DTO\GenerateTokenRequestDTO;
use App\Services\Auth\DTO\TwoFactorSaveSecretRequestDTO;
use App\Services\Auth\DTO\TwoFactorSetupRequestDTO;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Enums\EmailOTPActionEnum;
use App\Services\System\EmailOTPService;



/**
 * @OA\Tag(
 *     name="Two-Factor Authentication",
 *     description="APIs related to two-factor authentication (2FA)."
 * )
 */
class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly AccessTokenService $accessTokenService,
        private readonly EmailOTPService $emailOTPService,

    ) {}

    public function setup(): TwoFactorSetupResource
    {
        $responseDTO = $this->twoFactorService->setup(
            resolve(TwoFactorSetupRequestDTO::class)
                ->setEmail(Auth::user()->email)
                ->setCompanyName(config('app.name')),
        );


        return new TwoFactorSetupResource($responseDTO);
    }

    public function saveSecret(SaveSecretRequest $request): Response
    {
        $validated = $request->validated();

        $this->twoFactorService->validateAndSaveSecret(
            resolve(TwoFactorSaveSecretRequestDTO::class)
                ->setUserId(Auth::id())
                ->setGoogle2faSecret($validated['secret'])
                ->setGoogle2fa($validated['2fa'])
        );

        // Delete all OTP codes with TWO_FACTOR_SETUP action after successful validation
        $this->emailOTPService->deleteAllOTPCodesByAction(
            Auth::user()->email,
            EmailOTPActionEnum::TWO_FACTOR_SETUP
        );

        return response([
            'message' => __('auth.two-factor.save-secret'),
        ]);
    }

    public function verifyLogin(ValidateTwoFactorRequest $request): Response
    {
        $validatedData = $request->validated();

        $this->twoFactorService->checkTwoFactor(
            resolve(CheckTwoFactorRequestDTO::class)
                ->setGoogle2fa($validatedData['google2fa'])
                ->setUserId(Auth::id())
        );

        // Generate new access token
        $tokenResponse = $this->accessTokenService->generateToken(
            resolve(GenerateTokenRequestDTO::class)
                ->setUser(Auth::user())
                ->setIpAddress($request->ip())
                ->setUserAgent($request->userAgent())
                ->setTokenName('desktop')
        );

        return response([
            'message' => __('auth.login.success'),
            'data' => [
                'token' => new AccessTokenResource($tokenResponse),
            ],
        ], Response::HTTP_OK);
    }

    public function disable(DisableTwoFactorRequest $request)
    {
        $validatedData = $request->validated();
        $this->twoFactorService->disableTwoFactor(
            resolve(DisableTwoFactorRequestDTO::class)
                ->setGoogle2fa($validatedData['2fa'])
                ->setUserId(Auth::id())
        );

        return response([
            'message' => __('auth.two-factor.disable-success'),
        ]);
    }
}
