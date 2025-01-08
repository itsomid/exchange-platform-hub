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
        //Send Email
        $emailOtpService = resolve(EmailOTPService::class);
        $emailOtpService->send(
            resolve(SendOTPRequestDTO::class)
                ->setEmail(Auth::user()->email)
                ->setAction($action)
        );

        return response([
            'message' => 'email send successfully',
        ]);
    }
}
