<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Wallet\Portfolio\ReportLastWeekCollection;
use App\Http\Resources\V1\Wallet\Portfolio\Repost24HoursCollection;
use App\Services\Wallet\PortfolioService;
use Illuminate\Support\Facades\Auth;

class PortfolioController extends Controller
{
    public function __construct(private readonly PortfolioService $portfolioService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/portfolio/last-week",
     *     summary="Get Portfolio Report for Last Week",
     *     description="Retrieve a daily portfolio report for the last week, including total profit, profit percentage, and total balance.",
     *     tags={"Portfolio"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Portfolio report retrieved successfully.",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/PortfolioLastWeekReport")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getPortfolioLastWeek()
    {
        $reports = $this->portfolioService->calculatePortfolioForLastWeek(Auth::id());

        return new ReportLastWeekCollection($reports);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/portfolio/last-24-hours",
     *     summary="Get Portfolio Report for last 24 hours",
     *     description="Retrieve a daily portfolio report for the last 24 hours, including total profit, profit percentage, and total balance.",
     *     tags={"Portfolio"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Portfolio report retrieved successfully.",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/PortfolioLast24HoursReport")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getPortfolio24Hours()
    {
        $reports = $this->portfolioService->calculatePortfolioForLast24Hours(Auth::id());

        return new Repost24HoursCollection($reports);
    }
}
