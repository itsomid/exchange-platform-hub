<?php

namespace App\Http\Resources\V1\Wallet\Portfolio;

use App\Services\Wallet\DTO\Portfolio\PortfolioLastWeekResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="PortfolioLastWeekReport",
 *     type="object",
 *     title="Portfolio Last Week Report",
 *     description="Daily portfolio report for the last week.",
 *
 *     @OA\Property(
 *         property="report_date",
 *         type="string",
 *         format="date",
 *         description="The date of the report in YYYY-MM-DD format.",
 *         example="2024-12-15"
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
class ReportLastWeekCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn (PortfolioLastWeekResponseDTO $DTO) => [
            'report_date' => $DTO->getReportDate()->format('Y-m-d'),
            'total_profit' => $DTO->getTotalProfit(),
            'total_profit_percentage' => $DTO->getTotalProfitPercentage(),
            'total_balance' => $DTO->getTotalBalance(),
        ])->toArray();
    }
}
