<?php

namespace App\Services\Bot;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP wrapper around api-service's /api/internal/bot-admin endpoints
 * (bot-order cancel / user-wide cancel + liquidation).
 *
 * Configured via config/smart-bot.php:
 *   - api_url              : api-service base URL
 *   - internal_admin_token : matches BOT_INTERNAL_ADMIN_TOKEN on api-service
 */
class BotAdminApiClient
{
    private function http(): PendingRequest
    {
        $base  = (string) config('smart-bot.api_url');
        $token = (string) config('smart-bot.internal_admin_token');

        // Cancelling many tiers on the real exchange (cancel + market-sell per
        // tier) can take a while — allow a generous timeout.
        return Http::baseUrl(rtrim($base, '/').'/api/internal/bot-admin')
            ->acceptJson()
            ->timeout(180)
            ->withHeaders($this->headers($token));
    }

    public function cancelOrderPreview(int $orderId): Response
    {
        return $this->http()->post("/orders/{$orderId}/cancel-preview");
    }

    public function cancelOrder(int $orderId): Response
    {
        return $this->http()->post("/orders/{$orderId}/cancel");
    }

    public function cancelAllPreview(int $userId): Response
    {
        return $this->http()->post("/users/{$userId}/cancel-all-preview");
    }

    public function cancelAll(int $userId): Response
    {
        return $this->http()->post("/users/{$userId}/cancel-all");
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $token): array
    {
        $headers = ['X-Internal-Token' => $token];
        $admin   = auth('admin')->user();
        if (! $admin) {
            return $headers;
        }

        $headers['X-Admin-Id']    = (string) $admin->id;
        $headers['X-Admin-Label'] = $admin->email ?: ($admin->mobile ?: 'admin#'.$admin->id);

        return $headers;
    }
}
