<?php

namespace App\Services\Exchanges\ExchangeData;

interface ExchangeDataInterface
{
    public function fetchHistory(string $market, string $period, int $limit): array;

    public function fetchWithdrawalFee(string $currency): array;

    public function fetchMinTrade(): array;
}
