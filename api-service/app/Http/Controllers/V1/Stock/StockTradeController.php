<?php

namespace App\Http\Controllers\V1\Stock;

use App\Http\Controllers\Controller;
use App\Services\Stock\StockService;
use Illuminate\Http\JsonResponse;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Requests\V1\Stock\StockPurchaseRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Response;

class StockTradeController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    public function buy(StockPurchaseRequest $request)
    {
        try {

            $stockContract = $this->stockService->purchaseStock(
                Auth::user(),
                $request->validated()
            );
            return response([
                'message' => __('stock.stock_purchase_successful'),
                'data' => $stockContract
            ], Response::HTTP_CREATED);

        } catch (InsufficientBalanceException $e) {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function sell(): JsonResponse
    {
        try {
//            $result = $this->stockService->sellStock(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Stock sale successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
