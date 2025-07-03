<?php

namespace App\Http\Controllers\V1\Stock;

use App\Http\Controllers\Controller;
use App\Services\Stock\StockService;
use App\Http\Resources\V1\Stock\StockResource;
use Illuminate\Http\JsonResponse;
use App\Enums\StockTypeEnum;

class StockController extends Controller
{
    public function __construct(
        private readonly StockService $stockService
    ) {
    }

    public function index(): JsonResponse
    {
        $stocks = $this->stockService->getStockByType(StockTypeEnum::NORMAL->value);
        return response()->json([
            'data' => StockResource::collection($stocks)
        ]);
    }
} 