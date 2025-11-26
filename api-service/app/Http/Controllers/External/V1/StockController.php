<?php

namespace App\Http\Controllers\External\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\External\V1\StockPurchaseRequest;
use App\Http\Requests\External\V1\UserCreditTransactionsRequest;
use App\Services\Stock\StockService;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\ApiRequestRepositoryInterface;
use App\Repositories\Stock\StockRepositoryInterface;
use App\Repositories\DTO\ApiSystem\CreateApiRequestDTO;
use App\Enums\ApiRequestType;
use App\Models\Stock;
use App\Models\StockContract;
use App\Models\ApiSystem\ApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StockController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private UserRepositoryInterface $userRepository,
        private ApiRequestRepositoryInterface $apiRequestRepository,
        private StockRepositoryInterface $stockRepository
    ) {}

    /**
     * Purchase stock for a user
     */
    public function purchaseStock(StockPurchaseRequest $request)
    {
        $system = $request->system;
        // Pre-initialize variables so they are always defined for the catch block
        $apiRequest = null; // Will hold the ApiRequest record after successful creation
        $user = null;       // Will be set once user is found

        try {
            // Find user by email first
            $user = $this->userRepository->findByEmail($request->email);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربری با این ایمیل یافت نشد',
                    'error' => 'کاربری با این ایمیل یافت نشد'
                ], 400);
            }

            // Find stock to get price
            $stock = $this->stockRepository->getStockById($request->stock_id);
            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'سهام یافت نشد',
                    'error' => 'سهام یافت نشد'
                ], 400);
            }

            // Calculate quantity based on amount and stock price
            $quantity = round($request->amount / 104, 2);

            DB::beginTransaction();

            // Purchase stock using StockService (external flow without wallet impact)
            $result = $this->stockService->purchaseStockExternal($user, [
                'stock_id' => $request->stock_id,
                'amount' => $quantity,
                'description' => '[External API] ' . ($request->description ?? 'خرید سهام از طریق External API')
            ]);

            // Log API request with complete information after successful operation
            $apiRequest = $this->apiRequestRepository->create(
                resolve(CreateApiRequestDTO::class)
                    ->setSystemId($request->system->id)
                    ->setUserId($user->id)
                    ->setType(ApiRequestType::STOCK_PURCHASE)
                    ->setTrackingCode($request->tracking_code)
                    ->setModelType(StockContract::class)
                    ->setModelId($result->id ?? null)
                    ->setStatus('completed')
                    ->setRequestData([
                        'user_email' => $request->email,
                        'stock_id' => $request->stock_id,
                        'amount' => $request->amount,
                        'quantity' => $quantity,
                        'tracking_code' => $request->tracking_code,
                        'description' => $request->description . " (" . $system->name . ")"
                    ])
                    ->setResponseData([
                        'success' => true,
                        'user_id' => $user->id,
                        'contract_id' => $result->id ?? null,
                        'amount' => $request->amount,
                        'quantity' => $quantity,
                        'total_value' => $result->total_value ?? null
                    ])
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'خرید سهام با موفقیت انجام شد',
                'data' => [
                    'id' => $apiRequest->id,
                    'contract_id' => $result->contract_number ?? null,
                    'user_email' => $request->email,
                    'stock_id' => $request->stock_id,
                    'stock_name' => $stock->name,
                    'amount' => $request->amount,
                    'quantity' => $quantity,
                    'stock_price' => (float) $stock->value,
                    'total_value' => $result->total_value ?? null,
                    'contract_file_url' => $result->contract_file_url ?? null,
                    'description' => $request->description
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            // Log error
            Log::error('Stock purchase failed', [
                'system_id' => $system->id,
                'email' => $request->email,
                'stock_id' => $request->stock_id,
                'error' => $e->getMessage()
            ]);

            // Update API request with error response if it was created; otherwise create a failed record
            if ($apiRequest) {
                $this->apiRequestRepository->update($apiRequest->id, [
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                    'response_data' => [
                        'success' => false,
                        'error' => $e->getMessage()
                    ],
                    'processed_at' => now()
                ]);
            } elseif ($user) {
                // Ensure failed attempts are also logged for audit purposes
                $this->apiRequestRepository->create(
                    resolve(CreateApiRequestDTO::class)
                        ->setSystemId($system->id)
                        ->setUserId($user->id)
                        ->setType(ApiRequestType::STOCK_PURCHASE)
                        ->setTrackingCode($request->tracking_code)
                        ->setModelType(StockContract::class)
                        ->setStatus('failed')
                        ->setRequestData([
                            'user_email' => $request->email,
                            'stock_id' => $request->stock_id,
                            'amount' => $request->amount,
                            'tracking_code' => $request->tracking_code,
                            'description' => $request->description
                        ])
                        ->setResponseData([
                            'success' => false,
                            'error' => $e->getMessage()
                        ])
                );
            }

            return response()->json([
                'success' => false,
                'message' => 'خرید سهام با خطا مواجه شد',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get purchased stocks for a user
     */
    public function getPurchasedStocks(UserCreditTransactionsRequest $request): JsonResponse
    {
        try {
            // Get system from authenticated request (added by ApiSystemAuthMiddleware)
            $system = $request->system;
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            // Build query for API requests of type STOCK_PURCHASE for this system
            $query = ApiRequest::where('type', ApiRequestType::STOCK_PURCHASE)
                ->where('system_id', $system->id)
                ->where('status', 'completed');

            // Apply date filters if provided
            if ($request->filled('date_from')) {
                $query = $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query = $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            // Apply tracking_code filter if provided
            if ($request->filled('tracking_code')) {
                $query = $query->where('tracking_code', $request->tracking_code);
            }

            // Apply id filter if provided
            if ($request->filled('id')) {
                $query = $query->where('id', $request->id);
            }

            // Get total count for pagination
            $total = $query->count();

            // Get paginated results without cross-database relationships
            $stockPurchases = $query->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Collect contract IDs to fetch related data separately
            $contractIds = $stockPurchases->filter(function ($purchase) {
                return $purchase->model_type === 'App\Models\StockContract' && $purchase->model_id;
            })->pluck('model_id')->unique()->values();

            // Fetch contracts with their relationships from the default database
            $contracts = collect();
            $stocks = collect();
            $users = collect();

            if ($contractIds->isNotEmpty()) {
                $contracts = \App\Models\StockContract::whereIn('id', $contractIds)
                    ->with(['stock', 'user'])
                    ->get()
                    ->keyBy('id');

                $stocks = $contracts->pluck('stock')->filter()->keyBy('id');
                $users = $contracts->pluck('user')->filter()->keyBy('id');
            }

            // Format response data
            $formattedStocks = $stockPurchases->map(function ($purchase) use ($contracts, $stocks, $users) {
                $contract = null;
                $stock = null;
                $user = null;

                if ($purchase->model_type === 'App\Models\StockContract' && $purchase->model_id) {
                    $contract = $contracts->get($purchase->model_id);
                    if ($contract) {
                        $stock = $stocks->get($contract->stock_id);
                        $user = $users->get($contract->user_id);
                    }
                }

                return [
                    'id' => $purchase->id,
                    'tracking_code' => $purchase->tracking_code,
                    'contract_id' => $contract ? $contract->id : null,
                    'contract_number' => $contract ? $contract->contract_number : null,
                    'user_email' => $purchase->request_data['user_email'] ?? null,
                    'user_name' => $user ? $user->fullname() : null,
                    'stock_id' => $purchase->request_data['stock_id'] ?? null,
                    'stock_name' => $stock ? $stock->name : null,
                    'stock_type' => $stock ? $stock->type->value : null,
                    'amount' => $purchase->request_data['amount'] ?? null,
                    'quantity' => $purchase->request_data['quantity'] ?? null,
                    'price' => $stock ? $stock->value : null,
                    'total_amount' => $purchase->response_data['total_amount'] ?? ($contract ? $contract->total_value : null),
                    'contract_status' => $contract ? $contract->contract_status->value : null,
                    'description' => $purchase->request_data['description'] ?? null,
                    'purchase_date' => $purchase->created_at->toISOString(),
                    'contract_file_url' => $contract && $contract->contract_file ? $contract->contract_file_url : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'سهام‌های خریداری شده با موفقیت دریافت شد',
                'data' => [
                    'stocks' => $formattedStocks,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => ceil($total / $perPage),
                        'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : null,
                        'to' => $total > 0 ? min($page * $perPage, $total) : null,
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            Log::channel('api-system')->error('Error retrieving purchased stocks', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت سهام‌های خریداری شده: ' . $e->getMessage()
            ], 500);
        }
    }
}
