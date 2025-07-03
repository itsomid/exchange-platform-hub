<?php

namespace App\Repositories\Stock;

use App\Models\User;
use App\Models\Stock;
use App\Models\StockContract;
use Illuminate\Database\Eloquent\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function getStocks(): Collection
    {
        return Stock::all();
    }

    public function getStockById(string $stockId): ?Stock
    {
        return Stock::find($stockId);
    }

    public function getStockByType(string $type): Collection
    {
        return Stock::where('type', $type)
            ->where('status', \App\Enums\StockStatusEnum::ACTIVE)
            ->get();
    }

    public function getUserContracts(User $user): Collection
    {
        return StockContract::where('user_id', $user->id)
            ->with('stock')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createContract(User $user, float $amount, Stock $stock, float $totalValue): StockContract
    {
        return StockContract::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'contract_number' => $this->generateContractNumber(),
            'cancellation_fee' => $stock->cancellation_fee,
            'total_value' => $totalValue,
        ]);
    }

    public function getContractById(string $contractId): ?StockContract
    {
        return StockContract::where('id', $contractId)
            ->where('status', 'active')
            ->first();
    }

    public function cancelContract(StockContract $contract): bool
    {
        return $contract->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
    }

    public function getUserPortfolioValue(User $user): float
    {
        return StockContract::where('user_id', $user->id)
            ->where('status', 'active')
            ->sum('total_value');
    }

    private function generateContractNumber(): string
    {
        $datePart = now()->format('Ymd');
        $countToday = StockContract::whereDate('created_at', now()->toDateString())->count() + 1;
        $serial = str_pad($countToday, 4, '0', STR_PAD_LEFT);

        return "SH-{$datePart}-{$serial}";
    }

}
