<?php

namespace App\Http\Middleware;

use App\Helpers\JWT;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->header('authorization');

        if (! str_starts_with($bearerToken, 'Bearer ')) {
            return $this->unauthorizedResponse();
        }

        $token = str_replace('Bearer ', '', $bearerToken);
        $payload = JWT::new()->decode($token);
        if ($payload) {
            return $next($request);
        }

        return $this->unauthorizedResponse();

    }

    private function unauthorizedResponse(): Response
    {
        return response(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
    }
}
