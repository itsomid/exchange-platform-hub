<?php

namespace App\Services\Exchanges\ExchangeData;

interface ExchangeDataInterface
{
    public function fetchHistory(string $market, string $period, int $limit): array;

    public function fetchWithdrawalFee(string $currency): array;

    /**
     * @param  list<string>|null  $symbols  Pairs to load, e.g. BTCUSDT. Null loads the full catalog.
     */
    public function fetchMinTrade(?array $symbols = null): array;
}
