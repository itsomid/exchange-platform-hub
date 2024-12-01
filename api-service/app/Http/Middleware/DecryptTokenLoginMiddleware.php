<?php

namespace App\Http\Middleware;

use App\Services\Auth\LoginService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DecryptTokenLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $encryptedToken = $request->input('encrypted_token');
        if (empty($encryptedToken)) {
            return response([
                'message' => __('auth.login.encrypted_token_invalid'),
            ], 401);
        }
        $userId = resolve(LoginService::class)
            ->getDecryptedToken($encryptedToken);
        if (is_null($userId)) {
            return response([
                'message' => __('auth.login.encrypted_token_invalid'),
            ], 401);
        }
        Auth::loginUsingId($userId);

        return $next($request);
    }
}
