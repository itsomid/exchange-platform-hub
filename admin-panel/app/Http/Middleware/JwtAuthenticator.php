<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JWT;
use Closure;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class JwtAuthenticator
{
    public function handle($request, Closure $next)
    {

        $token = Request::hasHeader('authorization')
              ? Request::header('authorization')
              : Request::input('token');

        $token = Str::after($token, 'Bearer ');
        try {
            $payload = JWT::new()->decode($token);
            $user = User::find($payload->user_id);

            // Check User Existence
            if (is_null($user)) {
                return response()->json(['error' => 'This token has not defined'], 404);
            }

            // Sign In User
            auth('user')->loginUsingId($user->id);

            return $next($request);

        } catch (\Exception $exception) {
            return response()->json(['error' => 'Authentication failed. Please call to admin'], 401);
        }
    }
}
