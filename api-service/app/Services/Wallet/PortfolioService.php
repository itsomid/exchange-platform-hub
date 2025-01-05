<?php

namespace App\Services\Wallet;

use App\Repositories\Interfaces\MarketHistoryRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Wallet\DTO\Portfolio\PortfolioLastWeekResponseDTO;
use Carbon\Carbon;

class PortfolioService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private MarketHistoryRepositoryInterface $marketHistoryRepository
    ) {}

    public function calculatePortfolioForLastWeek(int $userId): array
    {
        // Fetch the user's assets
        $wallets = $this->walletRepository->getListsWithMarket($userId);

        // Initialize variables
        $portfolioReports = [];
        $previousTotalBalance = null;

        // Define the date range (last 7 days)
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(6);

        // Loop through each day in the range
        for ($date = $startDate; $date <= $endDate; $date->addDay()) {
            $totalBalance = 0;

            // Calculate the portfolio value for the current day
            foreach ($wallets as $wallet) {
                //USDT has not market
                if (is_null($wallet->market)) {
                    continue;
                }
                $marketHistory = $this->marketHistoryRepository->getByMarketIdWithDate($wallet->market->id, $date);
                if ($marketHistory) {
                    $totalBalance = bcadd(
                        $totalBalance,
                        bcmul($wallet->balance, $marketHistory->close, 8),
                        8
                    );
                }
            }

            // Calculate profit and profit percentage
            if ($previousTotalBalance !== null) {
                $totalProfit = $totalBalance - $previousTotalBalance;
                $totalProfitPercentage = $previousTotalBalance > 0
                    ? ($totalProfit / $previousTotalBalance) * 100
                    : 0;
            } else {
                $totalProfit = 0;
                $totalProfitPercentage = 0;
            }

            // Save the data for this day
            $portfolioReports[] = resolve(PortfolioLastWeekResponseDTO::class)
                ->setTotalProfit($totalProfit)
                ->setReportDate(clone $date)
                ->setTotalBalance($totalBalance)
                ->setTotalProfitPercentage($totalProfitPercentage);

            // Update previous balance
            $previousTotalBalance = $totalBalance;
        }

        return $portfolioReports;
    }
}
