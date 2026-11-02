<?php

namespace App\Services\Bot;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SpotBotOrderApiClient
{
    public function cancelForCurrency(int $currencyId): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl())
                ->acceptJson()
                ->timeout(60)
                ->withHeaders($this->headers())
                ->post('/currencies/'.$currencyId.'/cancel-orders');
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'ارتباط با سرویس ربات برقرار نشد.', 'cancelled' => 0];
        }

        $body = $response->json() ?? [];

        return [
            'ok' => $response->successful() && ($body['success'] ?? false) === true,
            'message' => $body['message'] ?? ($body['error'] ?? 'بستن سفارش‌ها انجام نشد.'),
            'cancelled' => (int) ($body['data']['total_cancelled'] ?? 0),
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('smart-bot.api_url'), '/').'/api/internal/spot-bot';
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = [
            'X-Internal-Token' => (string) config('smart-bot.internal_admin_token'),
        ];

        $admin = auth('admin')->user();
        if (! $admin) {
            return $headers;
        }

        $headers['X-Admin-Id'] = (string) $admin->id;
        $headers['X-Admin-Label'] = $admin->email ?: ($admin->mobile ?: 'admin#'.$admin->id);

        return $headers;
    }
}
