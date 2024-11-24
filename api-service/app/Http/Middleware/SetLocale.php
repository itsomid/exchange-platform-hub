<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the 'Accept-Language' header
        $locale = $request->header('Accept-Language', 'en'); // Default to 'en' if not provided

        $acceptLanguages = ['fa', 'en'];
        if (! in_array($locale, $acceptLanguages)) {
            $locale = 'en';
        }

        App::setFallbackLocale($locale);
        App::setLocale($locale); // Set the application locale
        Carbon::setLocale('fa');

        return $next($request);
    }
}
