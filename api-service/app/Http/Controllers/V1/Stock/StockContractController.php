<?php

namespace App\Http\Controllers\V1\Stock;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Stock\StockPortfolioCollection;
use App\Services\Stock\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\StockContractStatusEnum;

class StockContractController extends Controller
{
    public function __construct(
        private readonly StockService $stockService
    ) {
    }

    public function index(): StockPortfolioCollection
    {
        $portfolio = $this->stockService->getUserPortfolio(auth()->user());
        return new StockPortfolioCollection($portfolio);
    }



}
