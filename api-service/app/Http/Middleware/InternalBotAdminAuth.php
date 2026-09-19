<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the /api/internal/bot-admin/* endpoints (admin-panel → api-service).
 * Unlike InternalBotTestAuth this is NOT blocked in production — these are
 * real admin operations (order cancel / liquidation). The caller must present
 * an `X-Internal-Token` header matching config('smart-bot.internal_admin_token').
 * When the token is not configured the endpoints respond with 503 so they
 * cannot accidentally be left open.
 */
class InternalBotAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('smart-bot.internal_admin_token');
        if ($configured === '') {
            return response()->json([
                'error' => 'BOT_INTERNAL_ADMIN_TOKEN is not configured on api-service.',
            ], 503);
        }

        $provided = (string) $request->header('X-Internal-Token', '');
        if (! hash_equals($configured, $provided)) {
            return response()->json(['error' => 'Invalid internal token.'], 401);
        }

        return $next($request);
    }
}
