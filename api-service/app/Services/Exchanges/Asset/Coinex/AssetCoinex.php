<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;

class AssetCoinex implements AssetInterface
{
    public function getBalance(): array
    {
        $response = CoinexRequest::send(MethodEnum::GET, '/v2/assets/spot/balance');

        return array_map(function ($item) {
            return resolve(BalanceResponseDTO::class)
                ->setCcy($item['ccy'])
                ->setFrozen($item['frozen'])
                ->setAvailable($item['available']);
        }, $response->json('data'));
    }
}
