<?php

namespace App\Http\Controllers\Stock;

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
use Illuminate\Support\Facades\Storage;
use App\Services\Stock\StockService;

class StockContractController extends Controller
{
    protected $walletService;
    protected $stockService;
    protected $exchangeUserId;

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

    public function store(StockContractStoreRequest $request)
    {
        $stock = Stock::findOrFail($request->stock_id);
        $totalValue = $stock->value * $request->amount;

        $wallet = $this->walletService->getUserWallet($request->user_id, 'USDT');

        if ($stock->type !== StockTypeEnum::GIFT) {

            $hasBalance = $this->walletService->checkAndDecreaseBalance($request->user_id, 'USDT', $totalValue);
            if (!$hasBalance) {
                return redirect()->back()->withErrors(['balance' => 'موجودی کیف پول کاربر کافی نیست.']);
            }

        }

        $contractData = [
            'user_id' => $request->user_id,
            'stock_id' => $request->stock_id,
            'contract_number' => StockContract::generateContractNumber(),
            'amount' => $request->amount,
            'total_value' => $totalValue,
            'contract_status' => $request->contract_status,
            'cancellation_fee' => $stock->cancellation_fee,
            'description' => $request->description,
        ];

        if ($request->contract_status === 'sold') {
            $contractData['sold_at'] = now();
        } elseif ($request->contract_status === 'canceled') {
            $contractData['cancelled_at'] = now();
        }

        $contract = StockContract::create($contractData);

        Transaction::create([
            'user_id' => $request->user_id,
            'wallet_id' => $wallet->id,
            'amount' => -$totalValue,
            'balance' => $wallet->balance,
            'type' => TransactionTypeEnum::BUY,
            'subtype' => TransactionSubTypeEnum::STOCK,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => 'خرید سهام توسط ادمین (ID: #' . auth()->id() . ',' . \Auth::guard('admin')->user()->fullname() . ') - شماره قرارداد: ' . $contract->contract_number,
        ]);

        $username = $contract->user->username ?? 'user';
        $pdfPath = $username . '_' . $contract->contract_number . '.pdf';

        // Generate PDF using service
        $generatedPdfPath = $this->stockService->generateContractPdf($contract, $stock, $pdfPath);

        if ($generatedPdfPath) {
            $contract->update(['contract_file' => $generatedPdfPath]);
        } else {
            \Log::error('Failed to generate PDF for contract: ' . $contract->id);
            return redirect()->back()->withErrors(['pdf' => 'خطا در ایجاد فایل قرارداد.']);
        }

        return redirect()->route('admin.stock-contract.index')->with('success', 'قرارداد با موفقیت ایجاد شد.');
    }

    public function show(StockContract $stockContract)
    {
        $stockContract->load(['user', 'stock']);

        // Generate PDF if it doesn't exist
        $this->generateContractPdfIfNotExists($stockContract);

        return view('dashboard.stock_contract.show', compact('stockContract'));
    }

    /**
     * Generate contract PDF if it doesn't exist
     *
     * @param StockContract $stockContract
     * @return bool
     */
    public function generateContractPdfIfNotExists(StockContract $stockContract)
    {
        // Check if contract file already exists
        if ($stockContract->contract_file && $this->stockService->contractPdfExists($stockContract->contract_file)) {
            return true;
        }

        // Generate new PDF
        $username = $stockContract->user->username ?? 'user';
        $pdfPath = $username . '_' . $stockContract->contract_number . '.pdf';

        // Generate PDF using service
        $generatedPdfPath = $this->stockService->generateContractPdf($stockContract, $stockContract->stock, $pdfPath);

        if ($generatedPdfPath) {
            $stockContract->update(['contract_file' => $generatedPdfPath]);
            return true;
        } else {
            \Log::error('Failed to generate PDF for contract: ' . $stockContract->id);
            return false;
        }
    }

    /**
     * Regenerate contract PDF (force new generation)
     *
     * @param StockContract $stockContract
     * @return \Illuminate\Http\RedirectResponse
     */
    public function regeneratePdf(StockContract $stockContract)
    {
        // Delete existing PDF if it exists
        if ($stockContract->contract_file) {
            $this->stockService->deleteContractPdf($stockContract->contract_file);
        }

        // Generate new PDF
        $success = $this->generateContractPdfIfNotExists($stockContract);

        if ($success) {
            return redirect()->back()->with('success', 'فایل قرارداد با موفقیت بازسازی شد.');
        } else {
            return redirect()->back()->withErrors(['pdf' => 'خطا در بازسازی فایل قرارداد.']);
        }
    }

    /**
     * Generate PDFs for all contracts that don't have files
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateMissingPdfs()
    {
        $contractsWithoutPdf = StockContract::whereNull('contract_file')
            ->orWhere('contract_file', '')
            ->with(['user', 'stock'])
            ->get();

        $successCount = 0;
        $errorCount = 0;

        foreach ($contractsWithoutPdf as $contract) {
            $success = $this->generateContractPdfIfNotExists($contract);
            if ($success) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        $message = "تعداد {$successCount} فایل قرارداد با موفقیت ایجاد شد.";
        if ($errorCount > 0) {
            $message .= " تعداد {$errorCount} فایل با خطا مواجه شد.";
        }

        return redirect()->back()->with('success', $message);
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
