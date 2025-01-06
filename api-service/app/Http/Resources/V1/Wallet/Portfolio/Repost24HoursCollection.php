<?php

namespace App\Http\Resources\V1\Wallet\Portfolio;

use App\Services\Wallet\DTO\Portfolio\PortfolioLast24HoursResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Repost24HoursCollection extends ResourceCollection
{
    /**
     * @OA\Schema(
     *     schema="PortfolioLast24HoursReport",
     *     type="object",
     *     title="Portfolio Last 24 Hours Report",
     *     description="Daily portfolio report for the last 24 hours.",
     *
     *     @OA\Property(
     *         property="report_date",
     *         type="string",
     *         format="date",
     *         description="The date of the report in YYYY-MM-DD format.",
     *         example="2024-12-15 18:00"
     *     ),
     *     @OA\Property(
     *         property="total_profit",
     *         type="number",
     *         format="float",
     *         description="Total profit for the given date.",
     *         example=1500.75
     *     ),
     *     @OA\Property(
     *         property="total_profit_percentage",
     *         type="number",
     *         format="float",
     *         description="Total profit percentage for the given date.",
     *         example=2.45
     *     ),
     *     @OA\Property(
     *         property="total_balance",
     *         type="number",
     *         format="float",
     *         description="Total portfolio balance for the given date.",
     *         example=62000.50
     *     )
     * )
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn (PortfolioLast24HoursResponseDTO $DTO) => [
            'report_date' => $DTO->getReportDate()->format('Y-m-d H:i'),
            'total_profit' => $DTO->getTotalProfit(),
            'total_profit_percentage' => $DTO->getTotalProfitPercentage(),
            'total_balance' => $DTO->getTotalBalance(),
        ])->toArray();
    }
}
