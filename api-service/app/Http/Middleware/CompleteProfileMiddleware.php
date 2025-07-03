<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompleteProfileMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || empty($user->first_name) || empty($user->last_name) || empty($user->mobile) || empty($user->national_code)) {
            return response()->json([
                'message' => __('Please complete your profile information (first name, last name, mobile, national code).')
            ], 403);
        }
        return $next($request);
    }
} 