<?php

namespace App\Services\Wallet\DTO\Portfolio;

use Carbon\Carbon;

class PortfolioLast24HoursResponseDTO
{
    private Carbon $reportDate;

    private int $totalProfit;

    private float $totalProfitPercentage;

    private string $totalBalance;

    public function setReportDate(Carbon $reportDate): self
    {
        $this->reportDate = $reportDate;

        return $this;
    }

    public function getReportDate(): Carbon
    {
        return $this->reportDate;
    }

    public function setTotalProfit(int $totalProfit): self
    {
        $this->totalProfit = $totalProfit;

        return $this;
    }

    public function getTotalProfit(): int
    {
        return $this->totalProfit;
    }

    public function setTotalProfitPercentage(float $totalProfitPercentage): self
    {
        $this->totalProfitPercentage = $totalProfitPercentage;

        return $this;
    }

    public function getTotalProfitPercentage(): float
    {
        return $this->totalProfitPercentage;
    }

    public function setTotalBalance(string $totalBalance): self
    {
        $this->totalBalance = $totalBalance;

        return $this;
    }

    public function getTotalBalance(): string
    {
        return $this->totalBalance;
    }
}
