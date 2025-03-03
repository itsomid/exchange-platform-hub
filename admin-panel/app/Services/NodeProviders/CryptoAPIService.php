<?php


namespace App\Services\NodeProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Exception;

class CryptoAPIService
{
    protected $baseUrl = 'https://rest.cryptoapis.io/';

    /**
     * @throws Exception
     */
    public function getBalance(string $currency_symbol, string $address, string $network = 'mainnet')
    {
        $coinConfig = $this->getCoinConfig($currency_symbol);

        $url = $this->buildUrl($coinConfig, $currency_symbol, $network, $address);

        try {
            $response = Http::withHeaders([
                'X-API-Key' => Config::get('cryptoapis.api_key')
            ])->get($url);

            if ($response->successful()) {
                return $this->parseResponse($response->json());
            }

            return [
                'error' => 'API request failed',
                'details' => $response->json()
            ];
        } catch (Exception $e) {
            return [
                'error' => 'API request error',
                'details' => $e->getMessage()
            ];
        }
    }
    public function enrichWalletData($walletChain, $balanceData)
    {
        if (isset($balanceData['error'])) {
            $walletChain->api_error = $balanceData['error'];
            return $walletChain;
        }

        $walletChain->hot_balance = [
            'amount' => $balanceData['amount'],
            'unit' => $balanceData['unit']
        ];

        return $walletChain;
    }
    public function getCoinConfig($currency_symbol)
    {
        $config = Config::get("cryptoapis.coin_types.$currency_symbol");

        if (!$config) {
            throw new Exception("Unsupported coin: $currency_symbol");
        }

        return $config;
    }

    private function buildUrl($coinConfig, $currency_symbol, $network, $address)
    {
        if ($coinConfig['type'] === 'utxo') {
            return $this->baseUrl . "addresses-latest/utxo/{$coinConfig['chain']}/$network/$address/balance";
        }

        if ($coinConfig['type'] === 'evm') {
            return $this->baseUrl . "addresses-latest/evm/{$coinConfig['chain']}/$network/$address/balance";
        }

        throw new Exception("Invalid coin type");
    }

    private function parseResponse($response)
    {
        return [
            'amount' => $response['data']['item']['confirmedBalance']['amount'] ?? null,
            'unit' => $response['data']['item']['confirmedBalance']['unit'] ?? null,
        ];
    }
}
