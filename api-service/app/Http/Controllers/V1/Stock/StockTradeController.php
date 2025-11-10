<?php

namespace App\Http\Controllers\V1\Stock;

use App\Http\Controllers\Controller;
use App\Services\Stock\StockService;
use App\Exceptions\V1\Wallet\InsufficientBalanceException;
use App\Exceptions\V1\Stock\InvalidContractException;
use App\Http\Requests\V1\Stock\StockPurchaseRequest;
use Illuminate\Support\Facades\Auth;
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
        catch (\Exception $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sell(string $contractId)
    {
        try {
            $this->stockService->sellStock(Auth::user(), $contractId);

            return response()->json([
                'message' => __('stock.stock_sale_successful'),
            ], Response::HTTP_OK);
        } catch (InvalidContractException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing the sale'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
