<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;

class AssetCoinex implements AssetInterface
{
    public function getBalance(): array
    {
        $response = CoinexRequest::send(MethodEnum::GET, "/v2/assets/spot/balance");

        return array_map(function ($item){
            return resolve(BalanceResponseDTO::class)
                ->setCcy($item['ccy'])
                ->setFrozen($item['frozen'])
                ->setAvailable($item['available']);
        }, $response->json('data'));
    }

    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO
    {
        $requestBody = [
            'ccy' => $requestDTO->getCurrency(),
            'to_address' => $requestDTO->getAddress(),
            'withdraw_method' => $requestDTO->getWithdrawMethod()->value,
            'amount' => $requestDTO->getAmount(),
        ];
        if($requestDTO->getChain()){
            $requestBody['chain'] = $requestDTO->getChain();
        }

        $response = CoinexRequest::send(MethodEnum::POST, '/v2/assets/withdraw', $requestBody);

        $data = $response->json('data');

        return resolve(WithdrawResponseDTO::class)
                ->setWithdrawId($data['withdraw_id'])
                ->setCreatedAt($data['created_at'])
                ->setCurrency($data['ccy'])
                ->setChain($data['chain'])
                ->setAmount($data['amount'])
                ->setActualAmount($data['actual_amount'])
                ->setWithdrawMethod($data['withdraw_method'])
                ->setAddress($data['to_address'])
                ->setConfirmationCount($data['confirmations'])
                ->setExploreAddress($data['explorer_address_url'])
                ->setStatus($data['status'])
            ;
    }
}
