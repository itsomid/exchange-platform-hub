<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class DetectUserAgentChange
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $token = $request->bearerToken();
            $accessToken = PersonalAccessToken::findToken($token);

            $user = Auth::user();
            $currentAgent = $request->userAgent();
            $storedAgent = $accessToken->user_agent;

            if ($storedAgent && $storedAgent !== $currentAgent) {
                // Update the stored user agent
                $accessToken->user_agent = $currentAgent;
                $accessToken->save();
                // Send email notification
                Mail::to($user->email)->send(new \App\Mail\UserAgentChanged($user, $currentAgent));
            }
        }

        return $next($request);
    }
}
