<?php

namespace App\Http\Middleware;

use App\Enums\UserStatusEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        // Get authenticated user via Sanctum
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();

            if ($user->status === UserStatusEnum::SUSPEND) {
                // Revoke the current access token
                $user->currentAccessToken()->delete();

                // Return 401 Unauthorized response
                return response()->json([
                    'message' => __('user.suspended'),
                ], 401);
            }
        }

        return $next($request);
    }
}
