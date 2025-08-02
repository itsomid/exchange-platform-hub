<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get configuration from basic-auth config
        $config = config('basic-auth.default');
        $sessionKey = 'basic_auth_time';

        // Bypass authentication in local environment (e.g. when developing on localhost)
        if (app()->environment('local') || in_array($request->ip(), ['127.0.0.1', '::1']) || str_contains($request->getHost(), 'localhost')) {
            return $next($request);
        }
        
        // Handle logout request - ALWAYS check this first
        if ($request->has('logout')) {
            $request->session()->forget($sessionKey);
            
            // Return HTML response that forces browser to clear cached credentials
            $html = $this->getLogoutHtml($config['realm'], $request->url());
            
            return response($html, 401, [
                'Content-Type' => 'text/html',
                'WWW-Authenticate' => "Basic realm=\"{$config['realm']}\"",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        }
   
        // Check session authentication status
        $authTime = $request->session()->get($sessionKey);
        if ($authTime) {
            $validUntil = $authTime + $config['lifetime'];

            if (time() <= $validUntil) {
                // Session still valid
                return $next($request);
            }

            // Session expired – clear and force re-authentication
            $request->session()->forget($sessionKey);

            return $this->requestAuth($config['realm']);
        }

        // No valid session – check Authorization header
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            return $this->requestAuth($config['realm']);
        }

        // Decode the credentials
        $credentials = base64_decode(substr($authHeader, 6));
        [$providedUsername, $providedPassword] = explode(':', $credentials, 2);

        // Verify credentials
        if ($providedUsername !== $config['username'] || $providedPassword !== $config['password']) {
            return $this->requestAuth($config['realm']);
        }

        // Store authentication in session
        $this->storeSessionAuth($request, $sessionKey);

        return $next($request);
    }

    /**
     * Check if user is authenticated via session
     */
    private function isSessionAuthenticated(Request $request, string $sessionKey, int $lifetime): bool
    {
        $authTime = $request->session()->get($sessionKey);
     
        if (!$authTime) {
            return false;
        }

        // Check if authentication is still valid
        $validUntil = $authTime + $lifetime;

        if (time() > $validUntil) {
            // Authentication expired, remove from session
            $request->session()->forget($sessionKey);
            return false;
        }

        return true;
    }

    /**
     * Store authentication time in session
     */
    private function storeSessionAuth(Request $request, string $sessionKey): void
    {
        $request->session()->put($sessionKey, time());
    }

    /**
     * Request basic authentication
     */
    private function requestAuth(string $realm): Response
    {
        return response('Unauthorized', 401, [
            'WWW-Authenticate' => "Basic realm=\"{$realm}\""
        ]);
    }

    /**
     * Generate logout HTML page
     */
    private function getLogoutHtml(string $realm, string $currentUrl): string
    {
        $loginUrl = str_replace('?logout=1', '', $currentUrl);
        
        return '<!DOCTYPE html>
<html>
<head>
    <title>Logged Out</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-size: 24px; margin-bottom: 20px; }
        .realm { color: #666; margin-bottom: 20px; font-style: italic; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✓ Successfully logged out</div>
        <div class="realm">' . htmlspecialchars($realm) . '</div>
        <p>Your session has been cleared.</p>
        <a href="' . htmlspecialchars($loginUrl) . '" class="btn">Login Again</a>
        <script>setTimeout(() => window.location.href = "' . htmlspecialchars($loginUrl) . '", 3000);</script>
    </div>
</body>
</html>';
    }
}
