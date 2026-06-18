<?php

namespace App\Services\Bot;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP wrapper around api-service's /api/internal/bot-test endpoints.
 *
 * Configured via config/services.php['bot_api']:
 *   - url   : api-service base URL (e.g. http://api-service)
 *   - token : matches BOT_INTERNAL_TEST_TOKEN on api-service
 */
class BotTestApiClient
{
    private function http(): PendingRequest
    {
        $base  = (string) config('services.bot_api.url');
        $token = (string) config('services.bot_api.token');

        return Http::baseUrl(rtrim($base, '/').'/api/internal/bot-test')
            ->acceptJson()
            ->timeout(60)
            ->withHeaders(['X-Internal-Token' => $token]);
    }

    public function start(int $userId, float $capitalUsdt, ?float $mainBalance = null): Response
    {
        return $this->http()->post('/start', array_filter([
            'user_id'      => $userId,
            'capital_usdt' => $capitalUsdt,
            'main_balance' => $mainBalance,
        ], fn ($v) => $v !== null));
    }

    public function status(int $userId): Response
    {
        return $this->http()->get('/status', ['user_id' => $userId]);
    }

    public function bumpPrice(string $symbol, string $direction, float $step, string $mode = 'percent'): Response
    {
        return $this->http()->post('/bump-price', [
            'symbol'    => $symbol,
            'direction' => $direction,
            'step'      => $step,
            'mode'      => $mode,
        ]);
    }

    public function setPrice(string $symbol, float $price): Response
    {
        return $this->http()->post('/set-price', ['symbol' => $symbol, 'price' => $price]);
    }

    public function sync(): Response
    {
        return $this->http()->post('/sync');
    }

    public function reset(int $userId, ?float $mainBalance = null): Response
    {
        return $this->http()->post('/reset', array_filter([
            'user_id'      => $userId,
            'main_balance' => $mainBalance,
        ], fn ($v) => $v !== null));
    }
}
