<?php

namespace App\Services\Stock;

use App\Models\User;
use App\Models\StockContract;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Repositories\Stock\StockRepositoryInterface;
use App\Http\DTOs\Stock\StockSaleDTO;
use Illuminate\Support\Facades\DB;
use App\Exceptions\V1\Wallet\InsufficientBalanceException;
use App\Exceptions\InvalidContractException;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Wallet\WalletService;
use ZanySoft\LaravelPDF\Facades\PDF;
use Mpdf\Output\Destination;

use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionStatusEnum;

class StockService
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly WalletService $walletService,
        private readonly TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public function getStocks(): Collection
    {
        return $this->stockRepository->getStocks();
    }

    public function getStockById(string $stockId): ?Stock
    {
        return $this->stockRepository->getStockById($stockId);
    }

    public function getStockByType(string $type): Collection
    {
        return $this->stockRepository->getStockByType($type);
    }

    public function getUserPortfolio(User $user): array
    {
        $contracts = $this->stockRepository->getUserContracts($user);
        $totalValue = $this->stockRepository->getUserPortfolioValue($user);

        return [
            'contracts' => $contracts,
            'total_value' => $totalValue,
            'wallet_balance' => $this->walletRepository->getOneOrCreateByCurrencyWithLock('USDT', $user->id)
        ];
    }

    public function purchaseStock(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            // Check wallet balance
            $stock = $this->stockRepository->getStockById($data['stock_id']);
            $totalValue = $data['amount']*$stock->value;
            $hasBalance = $this->walletService->checkAndDecreaseBalance($user->id, 'USDT', $totalValue);
            if (!$hasBalance) {
                throw new InsufficientBalanceException('Insufficient balance in wallet');
            }

            $stockContract = $this->stockRepository->createContract(
                user: $user,
                amount: $data['amount'],
                stock: $stock,
                totalValue: $totalValue
            );
            $walletBaseCurrency = $this->walletRepository->getOneByCurrency('USDT', $user->id);
            // create transaction
            $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                ->setUserId($user->id)
                ->setType(TransactionTypeEnum::BUY)
                ->setAmount(-$totalValue)
                ->setCoinPrice(1)
                ->setStatus(TransactionStatusEnum::SUCCESS)
                ->setBalance($walletBaseCurrency->balance)
                ->setDescription('شماره قرارداد: ' . $stockContract->contract_number)
                ->setSubtype(TransactionSubTypeEnum::STOCK)
                ->setWalletId($walletBaseCurrency->id)
            );

            $generatedPdfPath = $this->generateContractPdf($stockContract, $stock);
            if ($generatedPdfPath) {
                $stockContract->update(['contract_file' => $generatedPdfPath]);
            } else {
                throw new \Exception('Failed to generate PDF for contract: ' . $stockContract->id);
            }

            return $stockContract;
        });
    }

    public function sellStock(User $user, StockSaleDTO $dto): array
    {
        return DB::transaction(function () use ($user, $dto) {
            $contract = $this->stockRepository->getContractById($dto->contractId);

            if (!$contract || $contract->user_id !== $user->id) {
                throw new InvalidContractException('Invalid contract');
            }

            if ($contract->is_gift) {
                throw new InvalidContractException('Gift contracts cannot be sold');
            }

            // Calculate fees
            $cancellationFee = $contract->amount * ($contract->cancellation_fee_percentage / 100);
            $returnAmount = $contract->amount - $cancellationFee;

            // Cancel contract
            $this->stockRepository->cancelContract($contract);

            // Return funds to wallet (minus fees)
            $this->walletService->credit(
                user: $user,
                amount: $returnAmount,
                currency: 'USDT',
                description: 'Stock Sale',
                metadata: [
                    'type' => 'stock_sale',
                    'contract_id' => $contract->id
                ]
            );

            return [
                'success' => true,
                'returned_amount' => $returnAmount,
                'fee_amount' => $cancellationFee
            ];
        });
    }


    public function generateContractPdf($contract, $stock, $filename = null)
    {
        try {

            // Font configuration
            $fontdata = [
                'iransans' => [
                    'R' => 'IRANSansWeb.ttf',
                    'B' => 'IRANSansWeb.ttf',
                    'I' => 'IRANSansWeb.ttf',
                    'BI' => 'IRANSansWeb.ttf',
                ]
            ];

            // Create PDF instance
            $pdf = PDF::make();
            $pdf->addCustomFont($fontdata, true);

            // Load the view
            $pdf->loadView('stock_contract.contract_pdf', [
                'contract' => $contract,
                'stock' => $stock,
            ]);

            // Generate filename if not provided
            if (!$filename) {
                $username = $contract->user->username ?? 'user';
                $filename = $username . '_' . $contract->contract_number . '.pdf';
            }

            // Ensure directory exists
            $directory = storage_path('app/public/contracts/stock');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $filepath = storage_path('app/public/contracts/stock/' . $filename);

            // Save the PDF
            $pdf->Output($filepath, Destination::FILE);

            // Check if file was created successfully
            if (file_exists($filepath)) {
                return $filename; // Return just the filename for database storage
            }

            return false;

        } catch (\Exception $e) {
            \Log::error('Stock contract PDF generation failed: ' . $e->getMessage(), [
                'contract_id' => $contract->id ?? 'unknown',
                'stock_id' => $stock->id ?? 'unknown',
                'filename' => $filename
            ]);

            return false;
        }
    }

    /**
     * Generate and stream a stock contract PDF
     *
     * @param mixed $contract
     * @param mixed $stock
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function streamContractPdf($contract, $stock, $filename = null)
    {
        // Font configuration
        $fontdata = [
            'iransans' => [
                'R' => 'IRANSansWeb.ttf',
                'B' => 'IRANSansWeb.ttf',
                'I' => 'IRANSansWeb.ttf',
                'BI' => 'IRANSansWeb.ttf',
            ]
        ];

        // Create PDF instance
        $pdf = PDF::make();
        $pdf->addCustomFont($fontdata, true);

        // Load the view
        $pdf->loadView('dashboard.stock_contract.contract_pdf', [
            'contract' => $contract,
            'stock' => $stock,
        ]);

        // Generate filename if not provided
        if (!$filename) {
            $filename = 'contract_' . $contract->contract_number . '.pdf';
        }

        // Stream the PDF
        return $pdf->stream($filename);
    }

    /**
     * Get stock contract PDF file path
     *
     * @param string $filename
     * @return string
     */
    public function getContractPdfPath($filename)
    {
        return storage_path('app/public/contracts/stock/' . $filename);
    }

    /**
     * Check if stock contract PDF exists
     *
     * @param string $filename
     * @return bool
     */
    public function contractPdfExists($filename)
    {
        return file_exists($this->getContractPdfPath($filename));
    }

    /**
     * Delete stock contract PDF file
     *
     * @param string $filename
     * @return bool
     */
    public function deleteContractPdf($filename)
    {
        $filepath = $this->getContractPdfPath($filename);

        if (file_exists($filepath)) {
            return unlink($filepath);
        }

        return false;
    }
}
