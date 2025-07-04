<?php

namespace App\Repositories\Stock;

use App\Models\User;
use App\Models\StockContract;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Stock;

interface StockRepositoryInterface
{
    /**
     * Get all stocks
     */
    public function getStocks(): Collection;

    /**
     * Get stock by ID
     */
    public function getStockById(string $stockId): ?Stock;

    /**
     * Get stock by type
     */
    public function getStockByType(string $type): Collection;

    /**
     * Get user's active stock contracts
     */
    public function getUserContracts(User $user): Collection;

    /**
     * Create a new stock contract
     */
    public function createContract(User $user, float $amount, Stock $stock, float $totalValue): StockContract;

    /**
     * Get contract by ID
     */
    public function getContractById(string $contractId): ?StockContract;

    /**
     * Cancel/Sell contract
     */
    public function cancelContract(StockContract $contract): bool;

    /**
     * Sell contract
     */
    public function sellContract(StockContract $contract): bool;

    /**
     * Get user's total stock portfolio value
     */
    public function getUserPortfolioValue(User $user): float;
}
