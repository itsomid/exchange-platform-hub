<?php

namespace App\Http\Controllers\External\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\External\V1\UserCreditIncreaseRequest;
use App\Http\Requests\External\V1\UserCreditTransactionsRequest;
use App\Services\Wallet\WalletService;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;

use App\Repositories\DTO\ApiSystem\CreateApiRequestDTO;
use App\Repositories\Interfaces\ApiRequestRepositoryInterface;
use App\Models\ApiSystem\ApiRequest;
use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\ApiRequestType;
use App\Models\Transaction;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UserCreditController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private UserRepositoryInterface $userRepository,
        private CurrencyRepositoryInterface $currencyRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private ApiRequestRepositoryInterface $apiRequestRepository,
        private WalletRepositoryInterface $walletRepository
    ) {}


    public function increaseCredit(UserCreditIncreaseRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->findByEmail($request->email);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر یافت نشد'
                ], 404);
            }

            $currency = $this->currencyRepository->getOne($request->currency);
            if (!$currency) {
                return response()->json([
                    'success' => false,
                    'message' => 'ارز یافت نشد'
                ], 404);
            }


            $increaseResult = $this->walletService->increaseBalance(
                $user->id,
                $request->currency,
                $request->amount
            );

            if (!$increaseResult) {
                throw new Exception('خطا در افزایش موجودی کاربر');
            }

            $wallet = $this->walletRepository->getOrCreateWallet(
                $user->id,
                $request->currency
            );

            $transaction = $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                    ->setUserId($user->id)
                    ->setWalletId($wallet->id)
                    ->setType(TransactionTypeEnum::DEPOSIT)
                    ->setSubtype(TransactionSubTypeEnum::API_SYSTEM)
                    ->setAmount($request->amount)
                    ->setBalance($wallet->balance)
                    ->setCoinPrice('1')
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription($request->description ?? 'افزایش اعتبار از طریق API سیستم')
            );

            $apiRequest = $this->apiRequestRepository->create(
                resolve(CreateApiRequestDTO::class)
                    ->setSystemId($request->system->id)
                    ->setUserId($user->id)
                    ->setType(ApiRequestType::USER_CREDIT_INCREASE)
                    ->setTrackingCode($request->tracking_code)
                    ->setModelType(Transaction::class)
                    ->setModelId($transaction->id)
                    ->setStatus('completed')
                    ->setRequestData([
                        'user_email' => $request->email,
                        'currency' => $request->currency,
                        'amount' => $request->amount,
                        'tracking_code' => $request->tracking_code,
                        'description' => $request->description
                    ])
                    ->setResponseData([
                        'user_id' => $user->id,
                        'currency' => $request->currency,
                        'amount' => $request->amount,
                    ])
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'اعتبار کاربر با موفقیت افزایش یافت',
                'data' => [
                    'id' => $apiRequest->id,
                    'tracking_code' => $request->tracking_code,
                    'user_email' => $user->email,
                    'currency' => $request->currency,
                    'amount' => $request->amount,
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            Log::channel('api-system')->error('Error increasing user credit', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در افزایش اعتبار کاربر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get credit transactions history for the system
     */
    public function getCreditTransactions(UserCreditTransactionsRequest $request): JsonResponse
    {
        try {
            // Get system from authenticated request (added by ApiSystemAuthMiddleware)
            $system = $request->system;
            $page = $request->get('page', 1);
            $perPage = 20;

            // Build query for API requests of type USER_CREDIT_INCREASE for this system
            $query = ApiRequest::where('type', ApiRequestType::USER_CREDIT_INCREASE)
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

            // Get paginated results
            $transactions = $query->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Format response data
            $formattedTransactions = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'tracking_code' => $transaction->tracking_code,
                    'user_email' => $transaction->request_data['user_email'] ?? null,
                    'currency' => $transaction->request_data['currency'] ?? null,
                    'amount' => $transaction->request_data['amount'] ?? null,
                    'description' => $transaction->request_data['description'] ?? null,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'لیست تراکنش‌های افزایش اعتبار با موفقیت دریافت شد',
                'data' => [
                    'transactions' => $formattedTransactions,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => ceil($total / $perPage),
                        'has_next_page' => ($page * $perPage) < $total,
                        'has_prev_page' => $page > 1,
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            Log::channel('api-system')->error('Error getting credit transactions', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست تراکنش‌ها: ' . $e->getMessage()
            ], 500);
        }
    }
}
