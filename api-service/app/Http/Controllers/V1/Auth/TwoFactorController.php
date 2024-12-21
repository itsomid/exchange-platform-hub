<?php

namespace App\Http\Controllers\V1\Auth;

use App\Enums\EmailOTPActionEnum;
use App\Exceptions\Auth\GoogleInvalidUserSecretKeyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\SaveSecretRequest;
use App\Http\Requests\V1\Auth\ValidateTwoFactorRequest;
use App\Http\Resources\Auth\AccessTokenResource;
use App\Http\Resources\TwoFactorSetupResource;
use App\Services\Auth\AccessTokenService;
use App\Services\Auth\DTO\CheckTwoFactorRequestDTO;
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

    /**
     * @OA\Get(
     *     path="/api/v1/2fa/setup",
     *     summary="Setup Two-Factor Authentication",
     *     description="Generates a QR code and secret key for setting up two-factor authentication (2FA).",
     *     operationId="setup2FA",
     *     tags={"Two-Factor Authentication"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="QR code and secret key for setting up 2FA",
     *
     *         @OA\JsonContent(ref="#/components/schemas/TwoFactorSetupResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized. User is not authenticated.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *      path="/api/v1/2fa/save-secret",
     *      summary="Save Two-Factor Authentication Secret",
     *      description="Validates and saves the 2FA secret key provided by the user.",
     *      operationId="save2FASecret",
     *      tags={"Two-Factor Authentication"},
     *      security={{"sanctum": {}}},
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(
     *              required={"2fa", "secret"},
     *
     *              @OA\Property(
     *                  property="2fa",
     *                  type="string",
     *                  description="The 2FA token entered by the user.",
     *                  example="123456"
     *              ),
     *              @OA\Property(
     *                  property="secret",
     *                  type="string",
     *                  description="The secret key used for generating 2FA tokens.",
     *                  example="JBSWY3DPEHPK3PXP"
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Secret saved successfully.",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Two-factor authentication secret saved successfully."
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Validation error or invalid 2FA token.",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="Invalid 2FA token or secret.")
     *          )
     *      )
     *  )
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws GoogleInvalidUserSecretKeyException
     * @throws SecretKeyTooShortException
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/2fa/verify-login",
     *     summary="Validate two-factor authentication code and generate access token",
     *     description="This endpoint accepts a two-factor authentication (2FA) code from the user and validates it. If the code is correct, it generates and returns an access token that can be used to authenticate further requests. The API will reject any invalid or missing 2FA code with an error.",
     *     operationId="validateTwoFactor",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidateTwoFactorRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Two-factor validation successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Login successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", ref="#/components/schemas/AccessTokenResource")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     ),
     *     security={
     *         {"sanctum": {}}
     *     }
     * )
     */
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
                ->setTokenName('desktop')
                ->setExpirationDate(now()->addMinutes(60)) // 1 hour
        );

        return response([
            'message' => __('auth.login.success'),
            'data' => [
                'token' => new AccessTokenResource($tokenResponse),
            ],
        ], Response::HTTP_OK);
    }
}
