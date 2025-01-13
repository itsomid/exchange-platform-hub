<?php

namespace App\Services\Exchanges\WithdrawalFee;

use Exception;
use Illuminate\Support\Facades\Http;

class CoinexService implements ExchangeInterface
{
    private string $baseUrl = 'https://api.coinex.com/v2';

    public function fetchWithdrawalFee(string $currency): array
    {
        $response = Http::get("{$this->baseUrl}/assets/deposit-withdraw-config", [
            'ccy' => $currency,
        ]);

        if ($response->failed()) {
            throw new Exception('Failed to fetch withdrawal fee from CoinEx.');
        }

        $data = $response->json();

        // Check if 'chains' data exists
        if (empty($data['data']['chains'])) {
            throw new Exception("No chain data available for currency {$currency}.");
        }

        $chains = $data['data']['chains'];

        // Map chains to extract relevant information
        $withdrawalFeesByNetwork = array_map(function ($chain) {
            return [
                'network' => $chain['chain'],
                'withdrawal_fee' => $chain['withdrawal_fee'],
                'deposit_enabled' => $chain['deposit_enabled'],
                'withdraw_enabled' => $chain['withdraw_enabled'],
            ];
        }, $chains);

        return [
            'currency' => $currency,
            'networks' => $withdrawalFeesByNetwork,
        ];
    }
}
