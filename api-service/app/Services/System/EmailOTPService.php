<?php

namespace App\Services\System;

use App\Mail\OTPDefaultMail;
use App\Repositories\DTO\System\EmailOTP\SaveNewEmailRequestDTO;
use App\Repositories\Interfaces\EmailOTPRepositoryInterface;
use App\Services\System\DTO\SendOTPRequestDTO;
use App\Services\System\DTO\VerifyOTPRequestDTO;
use App\Utils\RandomToken;
use Illuminate\Support\Facades\Mail;

class EmailOTPService
{
    const int CODE_LENGTH = 6;

    const int EXPIRATION_PER_MINUTES = 15;

    public function __construct(private EmailOTPRepositoryInterface $emailOTPRepository) {}

    /**
     * @OA\Post(
     *     path="/api/v1/authorization/otp-code/{action}",
     *     summary="Send OTP Code",
     *     description="Send an OTP code via email for a specific action, such as withdrawal or two-factor setup.",
     *     tags={"Authorization"},
     *
     *     @OA\Parameter(
     *         name="action",
     *         in="path",
     *         required=true,
     *         description="The action for which the OTP code is being sent. Possible values: `two-factor-setup`, `withdrawal`.",
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"two-factor-setup", "withdrawal"},
     *             example="withdrawal"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OTP code sent successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="email send successfully"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized. User is not authenticated.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Unauthenticated."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Invalid action parameter.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "action": {"The action field must be one of `two-factor-setup`, `withdrawal`."}
     *                 }
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function send(SendOTPRequestDTO $requestDTO)
    {
        $code = RandomToken::generate(self::CODE_LENGTH);

        $this->emailOTPRepository->saveNewEmail(
            resolve(SaveNewEmailRequestDTO::class)
                ->setEmail($requestDTO->getEmail())
                ->setCode($code)
                ->setAction($requestDTO->getAction())
        );

        $mailableClass = $requestDTO->getMailable() ?? OTPDefaultMail::class;

        Mail::to($requestDTO->getEmail())->send(
            new $mailableClass($code, $requestDTO->getName(),$requestDTO->getAction())
        );
    }

    public function verify(VerifyOTPRequestDTO $requestDTO): bool
    {
        $model = $this->emailOTPRepository->getLastToken(
            $requestDTO->getEmail(),
            $requestDTO->getAction()
        );

        if (is_null($model)) {
            return false;
        }
        $model->delete();

        return
            $model->code === $requestDTO->getCode()
            &&
            now()->subMinutes(self::EXPIRATION_PER_MINUTES)->lte($model->created_at);
    }
}
