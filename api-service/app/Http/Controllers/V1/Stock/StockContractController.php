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
use App\Repositories\Stock\StockRepositoryInterface;

class StockContractController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly StockRepositoryInterface $stockRepository
    ) {
    }

    public function index(): StockPortfolioCollection
    {
        $portfolio = $this->stockService->getUserPortfolio(Auth::user());
        return new StockPortfolioCollection($portfolio);
    }

    /**
     * Generate PDF for a stock contract
     *
     * @param Request $request
     * @param string $contractId
     * @return JsonResponse
     */
    public function generatePdf(Request $request, $contractId): JsonResponse
    {
     
        $contract = $this->stockRepository->getContractById($contractId);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'قرارداد یافت نشد.'
            ], 404);
        }
        $stock = $contract->stock;
        $filename = $this->stockService->generateContractPdf($contract, $stock);
        if ($filename) {
            return response()->json([
                'success' => true,
                'filename' => $filename,
                'url' => $contract->getContractFileUrlAttribute(),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'خطا در تولید فایل PDF قرارداد.'
            ], 500);
        }
    }
}
