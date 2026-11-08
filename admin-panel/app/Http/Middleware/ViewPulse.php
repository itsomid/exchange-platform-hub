<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ViewPulse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->mustSetUpTwoFactor() && ! Auth::user()->passwordChangeOverdue() && Auth::user()->can('viewPulse')) {
            return $next($request);
        }

        abort(401, 'Unauthorised');
    }
}
