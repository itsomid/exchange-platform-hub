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
                'min_deposit_amount' => $chain['min_deposit_amount'],
                'min_withdraw_amount' => $chain['min_withdraw_amount'],
                'safe_confirmations' => $chain['safe_confirmations'],
            ];
        }, $chains);

        return [
            'currency' => $currency,
            'networks' => $withdrawalFeesByNetwork,
        ];
    }

    public function fetchMinTrade(): array
    {
        $response = Http::get("{$this->baseUrl}/spot/market");

        if(!$response->successful()){
            throw new Exception('Failed to fetch data from CoinEx API');
        }

        return array_map(function(array $item){
            return [
                'market' => $item['market'],
                'min_amount' => $item['min_amount'],
                'base_ccy' => $item['base_ccy'],
                'quote_ccy' => $item['quote_ccy']
            ];
        }, $response->json('data'));
    }
}
