<?php

namespace App\Http\Middleware;

use App\Enums\EmailOTPActionEnum;
use App\Services\System\DTO\VerifyOTPRequestDTO;
use App\Services\System\EmailOTPService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyOTPEmailMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $code = $request->input('otp_code');

        $service = resolve(EmailOTPService::class);
        if (empty($code) || ! $service->verify(
            resolve(VerifyOTPRequestDTO::class)
                ->setEmail(Auth::user()->email)
                ->setCode($code)
                ->setAction(EmailOTPActionEnum::from($action))
        )) {
            return response([
                'error' => __('otp_service.code_invalid'),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $next($request);
    }
}
