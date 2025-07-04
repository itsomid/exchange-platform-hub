<?php

namespace App\Http\Controllers\Stock;

use App\Data\FileStoragePaths;
use App\Enums\StockContractStatusEnum;
use App\Enums\StockTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StockContractStoreRequest;
use App\Http\Requests\Stock\StockContractUpdateRequest;

use App\Models\Stock;
use App\Models\StockContract;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Wallet\WalletService;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StockContractController extends Controller
{
    protected $walletService;
    protected $stockService;
    protected $bitexroomUserId;

    public function __construct(WalletService $walletService, StockService $stockService)
    {
        $this->bitexroomUserId = config('bitexroom.user_id', 1);
        $this->walletService = $walletService;
        $this->stockService = $stockService;
    }

    public function index()
    {
        $contracts = StockContract::with(['user', 'stock'])->get();

        // Dashboard statistics
        $totalContracts = $contracts->count();
        $soldContracts = $contracts->where('contract_status', StockContractStatusEnum::SOLD)->count();
        $canceledContracts = $contracts->where('contract_status', StockContractStatusEnum::CANCELED)->count();
        $soldAmount = $contracts->where('contract_status', StockContractStatusEnum::SOLD)->sum('total_value');
        $canceledAmount = $contracts->where('contract_status', StockContractStatusEnum::CANCELED)->sum('total_value');
        $cancellationFees = $contracts->where('contract_status', StockContractStatusEnum::CANCELED)->sum('cancellation_fee');

        return view('dashboard.stock_contract.index', [
            'contracts' => $contracts,
            'totalContracts' => $totalContracts,
            'soldContracts' => $soldContracts,
            'canceledContracts' => $canceledContracts,
            'soldAmount' => $soldAmount,
            'canceledAmount' => $canceledAmount,
            'cancellationFees' => $cancellationFees,
        ]);
    }

    public function create()
    {
        $stocks = Stock::where('status', 'active')->get();
        $users = User::all();
        return view('dashboard.stock_contract.create', compact('stocks', 'users'));
    }

    /**
     * Call external API to generate contract PDF
     * @param int $contractId
     * @return string|null  PDF path or null on failure
     */
    private function callExternalPdfApi($contractId, $userId)
    {
        $baseUrl = config('bitexroom.contracts.base_url');
        $url = $baseUrl . "/api/v1/stocks/contracts/{$contractId}/generate-pdf";

        $token = User::find($userId)->generateAccessToken(10);
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->post($url);

            if ($response->successful() && isset($response->json()['filename'])) {
                return $response->json()['filename'];
            } else {
                Log::error('PDF API error', ['response' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error('PDF API exception', ['error' => $e->getMessage()]);
        }
        return null;
    }

    public function store(StockContractStoreRequest $request)
    {
        $stock = Stock::findOrFail($request['stock_id']);
        $totalValue = $stock->value * $request['amount'];

        $wallet = $this->walletService->getUserWallet($request['user_id'], 'USDT');

        if ($stock->type !== StockTypeEnum::GIFT) {
            $hasBalance = $this->walletService->checkAndDecreaseBalance($request['user_id'], 'USDT', $totalValue);
            if (!$hasBalance) {
                return redirect()->back()->withErrors(['balance' => 'موجودی کیف پول کاربر کافی نیست.']);
            }
        }

        $contractData = [
            'user_id' => $request['user_id'],
            'stock_id' => $request['stock_id'],
            'contract_number' => StockContract::generateContractNumber(),
            'amount' => $request['amount'],
            'total_value' => $totalValue,
            'contract_status' => $request['contract_status'],
            'cancellation_fee' => $stock->cancellation_fee,
            'description' => $request['description'],
        ];

        if ($request['contract_status'] === 'sold') {
            $contractData['sold_at'] = now();
        } elseif ($request['contract_status'] === 'canceled') {
            $contractData['cancelled_at'] = now();
        }

        $contract = StockContract::create($contractData);

        Transaction::create([
            'user_id' => $request['user_id'],
            'wallet_id' => $wallet->id,
            'amount' => -$totalValue,
            'balance' => $wallet->balance,
            'type' => TransactionTypeEnum::BUY,
            'subtype' => TransactionSubTypeEnum::STOCK,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => 'خرید سهام توسط ادمین (UserID: #' . $request['user_id'] . ') - شماره قرارداد: ' . $contract->contract_number,
        ]);

        // Generate PDF using external API
        $generatedPdfFilename    = $this->callExternalPdfApi($contract->id, $request['user_id']);

        if ($generatedPdfFilename) {
            $contract->update(['contract_file' => $generatedPdfFilename]);
        } else {
            Log::error('Failed to generate PDF for contract: ' . $contract->id);
            return redirect()->back()->withErrors(['pdf' => 'خطا در ایجاد فایل قرارداد.']);
        }

        return redirect()->route('admin.stock-contract.index')->with('success', 'قرارداد با موفقیت ایجاد شد.');
    }

    public function show(StockContract $stockContract)
    {
        $stockContract->load(['user', 'stock']);


        return view('dashboard.stock_contract.show', compact('stockContract'));
    }

    /**
     * Generate contract PDF if it doesn't exist
     *
     * @param StockContract $stockContract
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateContractPdfIfNotExists(StockContract $stockContract)
    {
        if ($stockContract->contract_file) {
            $fileUrl = FileStoragePaths::CONTRACT_DOWNLOAD_URL($stockContract->contract_file);
            $response = Http::head($fileUrl);
            if ($response->ok()) {
                return redirect()->back()->with('success', 'فایل قرارداد موجود است');
            }
        }

        $generatedPdfFilename = $this->callExternalPdfApi($stockContract->id, $stockContract->user_id);

        if ($generatedPdfFilename) {
            $stockContract->update(['contract_file' => $generatedPdfFilename]);
            return redirect()->back()->with('success', 'فایل با موفقیت بازسازی شد');
        } else {
            Log::error('Failed to generate PDF for contract: ' . $stockContract->id);
            return redirect()->back()->withErrors(['pdf' => 'خطا در بازسازی فایل قرارداد']);
        }
    }

    public function edit(StockContract $stockContract)
    {
        $stocks = Stock::where('status', 'active')->get();
        $users = User::all();
        return view('dashboard.stock_contract.edit', compact('stockContract', 'stocks', 'users'));
    }

    public function update(StockContractUpdateRequest $request, StockContract $stockContract)
    {
        $updateData = [
            'contract_status' => $request->contract_status,
            'description' => $request->description,
        ];

        // Handle status changes and timestamps
        if ($request->contract_status === 'sold' && $stockContract->contract_status !== StockContractStatusEnum::SOLD) {
            $updateData['sold_at'] = now();
        } elseif ($request->contract_status === 'canceled' && $stockContract->contract_status !== StockContractStatusEnum::CANCELED) {
            $updateData['cancelled_at'] = now();

            // Refund logic
            $refundAmount = $stockContract->total_value - $stockContract->cancellation_fee;
            if ($refundAmount > 0) {
                $user = $stockContract->user;
                $wallet = $this->walletService->getUserWallet($user->id, 'USDT');
                $ExchangeWallet = $this->walletService->getExchangeWallet('USDT');

                // Create transaction record
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $refundAmount,
                    'balance' => $wallet->balance,
                    'type' => TransactionTypeEnum::SELL,
                    'subtype' => TransactionSubTypeEnum::STOCK,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => 'بازگشت وجه ابطال قرارداد سهام' . $stockContract->contract_number,
                ]);

                Transaction::create([
                    'user_id' => $this->bitexroomUserId,
                    'wallet_id' => $ExchangeWallet->id,
                    'amount' => $stockContract->cancellation_fee,
                    'balance' => $ExchangeWallet->balance,
                    'type' => TransactionTypeEnum::FEE,
                    'subtype' => TransactionSubTypeEnum::STOCK,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => 'کارمزد ابطال قرارداد' . $stockContract->contract_number,
                ]);

                // Update wallet balance using WalletService
                $this->walletService->increaseBalance($user->id, 'USDT', $refundAmount);
            }
        }
        $stockContract->update($updateData);

        return redirect()->route('admin.stock-contract.index')->with('success', 'قرارداد با موفقیت بروزرسانی شد.');
    }

    public function destroy(StockContract $stockContract)
    {

        $stockContract->delete();
        return redirect()->route('admin.stock-contract.index')->with('success', 'قرارداد با موفقیت حذف شد.');
    }
}
