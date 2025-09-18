<?php

namespace App\Http\Controllers\V1\Authorization;

use App\Enums\EmailOTPActionEnum;
use App\Http\Controllers\Controller;
use App\Services\System\DTO\SendOTPRequestDTO;
use App\Services\System\EmailOTPService;
use Illuminate\Support\Facades\Auth;

class EmailOTPController extends Controller
{
    public function send(EmailOTPActionEnum $action)
    {
        try {
            //Send Email
            $emailOtpService = resolve(EmailOTPService::class);
            $emailOtpService->send(
                resolve(SendOTPRequestDTO::class)
                    ->setEmail(Auth::user()->email)
                    ->setAction($action)
            );

            return response([
                'message' => __('messages.otp.send'),
            ]);
        } catch (\Exception $e) {
            if ($e->getCode() === 429) {
                return response([
                    'message' => $e->getMessage(),
                ], 429);
            }
            throw $e;
        }
    }
}
