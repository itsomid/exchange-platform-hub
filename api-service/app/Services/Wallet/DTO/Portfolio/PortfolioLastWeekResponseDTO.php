<?php

namespace App\Services\Wallet\DTO\Portfolio;

use Carbon\Carbon;

class PortfolioLastWeekResponseDTO
{
    private Carbon $reportDate;

    private int $totalProfit;

    private float $totalProfitPercentage;

    private string $totalBalance;

    public function setReportDate(Carbon $reportDate): PortfolioLastWeekResponseDTO
    {
        $this->reportDate = $reportDate;

        return $this;
    }

    public function getReportDate(): Carbon
    {
        return $this->reportDate;
    }

    public function setTotalProfit(int $totalProfit): PortfolioLastWeekResponseDTO
    {
        $this->totalProfit = $totalProfit;

        return $this;
    }

    public function getTotalProfit(): int
    {
        return $this->totalProfit;
    }

    public function setTotalProfitPercentage(float $totalProfitPercentage): PortfolioLastWeekResponseDTO
    {
        $this->totalProfitPercentage = $totalProfitPercentage;

        return $this;
    }

    public function getTotalProfitPercentage(): float
    {
        return $this->totalProfitPercentage;
    }

    public function setTotalBalance(string $totalBalance): PortfolioLastWeekResponseDTO
    {
        $this->totalBalance = $totalBalance;

        return $this;
    }

    public function getTotalBalance(): string
    {
        return $this->totalBalance;
    }
}
