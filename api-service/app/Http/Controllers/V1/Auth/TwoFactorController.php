<?php

namespace App\Http\Controllers\V1\Auth;

use App\Enums\EmailOTPActionEnum;
use App\Exceptions\Auth\GoogleInvalidUserSecretKeyException;
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
use App\Services\System\DTO\SendOTPRequestDTO;
use App\Services\System\EmailOTPService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

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

    ) {}

    public function setup(): TwoFactorSetupResource
    {
        $responseDTO = $this->twoFactorService->setup(
            resolve(TwoFactorSetupRequestDTO::class)
                ->setEmail(Auth::user()->email)
                ->setCompanyName(config('app.name')),
        );

        //Send Email
        $emailOtpService = resolve(EmailOTPService::class);
        $emailOtpService->send(
            resolve(SendOTPRequestDTO::class)
                ->setEmail(Auth::user()->email)
                ->setAction(EmailOTPActionEnum::TWO_FACTOR_SETUP)
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/2fa/disable",
     *     summary="Disable two-factor authentication",
     *     description="This endpoint disables two-factor authentication (2FA) for the authenticated user.",
     *     operationId="disableTwoFactor",
     *     tags={"Two-Factor Authentication"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"2fa"},
     *             ref="#/components/schemas/DisableTwoFactorRequest"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Two-factor authentication disabled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Two-factor authentication disabled successfully."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties={
     *                     @OA\Property(
     *                         property="field",
     *                         type="array",
     *
     *                         @OA\Items(type="string", example="The 2fa field is required.")
     *                     )
     *                 }
     *             )
     *         )
     *     )
     * )
     */
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
