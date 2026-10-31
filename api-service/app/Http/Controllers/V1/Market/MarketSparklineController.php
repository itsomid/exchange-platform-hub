<?php

namespace App\Http\Controllers\V1\Market;

use App\Repositories\Interfaces\MarketHistoryRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketSparklineController
{
    public function __construct(
        private readonly MarketHistoryRepositoryInterface $marketHistoryRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if ($request->filled('symbol')) {
            $symbol = strtoupper(trim((string) $request->query('symbol')));
            $sparklines = $this->marketHistoryRepository->getWeeklySparklines([$symbol]);

            return response()->json([
                'data' => [
                    'symbol' => $symbol,
                    'prices' => $sparklines[$symbol] ?? [],
                ],
            ]);
        }

        $requestedSymbols = [];
        if ($request->filled('symbols')) {
            $requestedSymbols = collect(explode(',', (string) $request->query('symbols')))
                ->map(fn (string $symbol) => strtoupper(trim($symbol)))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return response()->json([
            'data' => $this->marketHistoryRepository->getWeeklySparklines($requestedSymbols),
        ]);
    }
}
