<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActivation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()->is_active) {
            return $next($request);
        }

        auth()->logout();

        return redirect()->route('login')->with(['status' => 'حساب کاربری شما مسدود شده است.']);
    }
}
