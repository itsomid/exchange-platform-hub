<?php

namespace App\Services\Exchanges\WithdrawalFee;

interface ExchangeInterface
{
    public function fetchWithdrawalFee(string $currency): array;

    public function fetchMinTrade(): array;
}
