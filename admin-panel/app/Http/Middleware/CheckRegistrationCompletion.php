<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRegistrationCompletion
{
    public function handle(Request $request, Closure $next): Response
    {

        if (auth('user')->check() && empty(auth('user')->user()->name)) {
            return redirect()->route('user.auth.otp.register');
        }

        //        if (auth('user')->check() && !auth('user')->user()->verified)
        //            return redirect()->route('user.auth.otp.term-condition');

        return $next($request);
    }
}
