<?php

namespace App\Jobs;

use App\Enums\CurrencyChainEnum;
use App\Enums\DepositStatusEnum;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\Deposit;
use App\Models\HdWalletOutgoingTransaction;
use App\Services\NodeProviders\BlockchairService;
use App\Services\NodeProviders\EtherScanService;
use App\Services\NodeProviders\TronScanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncHdWalletOutgoingTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Only try once

    protected string $syncId;
    protected string $currencySymbol;
    protected int $currencyChainId;
    protected int $apiDelay;
    protected array $indices;

    /**
     * Create a new job instance.
     */
    public function __construct(string $syncId, string $currencySymbol, int $currencyChainId, int $apiDelay = 500, array $indices = [])
    {
        $this->syncId = $syncId;
        $this->currencySymbol = $currencySymbol;
        $this->currencyChainId = $currencyChainId;
        $this->apiDelay = $apiDelay;
        $this->indices = $indices;
        $this->onQueue('hd-wallet-sync');
    }
    /**
     * Execute the job.
     */
    public function handle(
        TronScanService $tronScanService,
        EtherScanService $etherScanService,
        BlockchairService $blockchairService
    ): void {
        try {
            // Initial progress
            $this->updateProgress(0, 'در حال دریافت لیست آدرس‌ها...', 'processing');

            // Get currency and chain
            $currency = Currency::where('symbol', $this->currencySymbol)->first();
            $chain = CurrencyChain::find($this->currencyChainId);

            if (!$currency || !$chain) {
                $this->updateProgress(0, 'کوین یا شبکه یافت نشد', 'error');
                return;
            }

            // Get addresses to sync
            $addresses = $this->getAddressesToProcess($chain, $currency);

            if ($addresses->isEmpty()) {
                $this->updateProgress(100, 'هیچ آدرسی برای sync یافت نشد', 'completed', [
                    'total_addresses' => 0,
                    'total_new_transactions' => 0,
                    'errors' => [],
                ]);
                return;
            }

            $totalAddresses = $addresses->count();
            $processedAddresses = 0;
            $totalNewTransactions = 0;
            $errors = [];
            $processedAddressList = [];

            $this->updateProgress(0, "در حال پردازش {$totalAddresses} آدرس...", 'processing', [
                'total_addresses' => $totalAddresses,
                'processed_addresses' => [],
            ]);

            foreach ($addresses as $addressRecord) {
                $processedAddresses++;
                $progress = (int) (($processedAddresses / $totalAddresses) * 100);

                // Update progress with current address
                $this->updateProgress(
                    $progress,
                    "در حال پردازش آدرس: {$addressRecord->address} (ایندکس {$addressRecord->user_id})",
                    'processing',
                    [
                        'current_address' => $addressRecord->address,
                        'current_index' => $addressRecord->user_id,
                        'processed_count' => $processedAddresses,
                        'total_addresses' => $totalAddresses,
                        'processed_addresses' => $processedAddressList,
                    ]
                );

                try {
                    $newCount = $this->syncAddressTransactions(
                        $addressRecord,
                        $chain,
                        $currency,
                        $tronScanService,
                        $etherScanService,
                        $blockchairService
                    );

                    $totalNewTransactions += $newCount;

                    // Add to processed list
                    $processedAddressList[] = [
                        'address' => $addressRecord->address,
                        'index' => $addressRecord->user_id,
                        'new_transactions' => $newCount,
                        'status' => 'success',
                    ];
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    $errors[] = [
                        'address' => $addressRecord->address,
                        'index' => $addressRecord->user_id,
                        'error' => $errorMsg,
                    ];

                    // Add to processed list with error
                    $processedAddressList[] = [
                        'address' => $addressRecord->address,
                        'index' => $addressRecord->user_id,
                        'status' => 'error',
                        'error' => $errorMsg,
                    ];

                    Log::error("Sync outgoing transactions failed for {$addressRecord->address}: {$errorMsg}");
                }

                // Delay between API calls
                usleep($this->apiDelay * 1000);
            }

            // Final progress
            $this->updateProgress(
                100,
                "همگام‌سازی با موفقیت انجام شد",
                'completed',
                [
                    'total_addresses' => $totalAddresses,
                    'total_new_transactions' => $totalNewTransactions,
                    'errors' => $errors,
                    'processed_addresses' => $processedAddressList,
                ]
            );

            Log::info("Sync completed for {$this->currencySymbol} on chain {$chain->chain->value}", [
                'total_addresses' => $totalAddresses,
                'total_new_transactions' => $totalNewTransactions,
                'errors_count' => count($errors),
            ]);
        } catch (\Exception $e) {
            Log::error("Fatal error in sync job: " . $e->getMessage(), [
                'sync_id' => $this->syncId,
                'exception' => $e,
            ]);

            $this->updateProgress(
                0,
                'خطای کلی در پردازش: ' . $e->getMessage(),
                'error',
                ['fatal_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get addresses to process
     */
    protected function getAddressesToProcess($chain, $currency)
    {
        $query = Deposit::query()
            ->where('status', DepositStatusEnum::CONFIRMED)
            ->whereNotNull('transaction_hash')
            ->where('currency_chain_id', $chain->id)
            ->whereNotNull('address');

        if ($currency) {
            $query->where('currency_symbol', $currency->symbol);
        }

        // Filter to selected indices only when provided
        if (!empty($this->indices)) {
            $query->whereIn('user_id', $this->indices);
        }

        return $query->select([
            'address',
            'user_id',
            DB::raw("'{$chain->chain->value}' as chain"),
        ])->distinct('address')->get();
    }

    /**
     * Sync outgoing transactions for a single address
     */
    protected function syncAddressTransactions(
        $addressRecord,
        $chain,
        $currency,
        TronScanService $tronScanService,
        EtherScanService $etherScanService,
        BlockchairService $blockchairService
    ): int {
        $chainEnum = $chain->chain;

        if (!$chainEnum instanceof CurrencyChainEnum) {
            return 0;
        }

        // Get the latest synced block number
        $latestBlockNumber = HdWalletOutgoingTransaction::where('from_address', $addressRecord->address)
            ->where('currency_chain_id', $chain->id)
            ->max('block_number');

        // Fetch transactions from blockchain
        $result = $this->fetchOutgoingTransactions(
            $chainEnum,
            $addressRecord->address,
            $currency?->symbol,
            $latestBlockNumber,
            $tronScanService,
            $etherScanService,
            $blockchairService
        );

        if (isset($result['error'])) {
            throw new \Exception($result['error'] . ': ' . ($result['details'] ?? ''));
        }

        $transactions = $result['transactions'] ?? [];
        $newCount = 0;

        foreach ($transactions as $tx) {
            // Skip zero-amount transactions (common phishing/dust transactions)
            if (!isset($tx['amount']) || bccomp((string) $tx['amount'], '0', 18) <= 0) {
                continue;
            }

            // Skip if already exists
            if (HdWalletOutgoingTransaction::where('transaction_hash', $tx['transaction_hash'])->exists()) {
                continue;
            }

            // Determine currency
            $txCurrency = $currency;
            if (!$txCurrency && isset($tx['currency_symbol'])) {
                $txCurrency = Currency::where('symbol', $tx['currency_symbol'])->first();
            }

            if (!$txCurrency) {
                $txCurrency = $this->getNativeCurrency($chainEnum);
            }

            if (!$txCurrency) {
                continue;
            }

            try {
                HdWalletOutgoingTransaction::create([
                    'user_id' => $addressRecord->user_id,
                    'currency_symbol' => $txCurrency->symbol,
                    'currency_chain_id' => $chain->id,
                    'amount' => $tx['amount'],
                    'transaction_hash' => $tx['transaction_hash'],
                    'from_address' => $tx['from_address'],
                    'to_address' => $tx['to_address'],
                    'block_number' => $tx['block_number'],
                    'transaction_at' => $tx['transaction_at']
                        ? \Carbon\Carbon::createFromTimestamp($tx['transaction_at'])
                        : now(),
                    'source' => 'sync',
                ]);
                $newCount++;
            } catch (\Exception $e) {
                Log::warning("Could not save transaction {$tx['transaction_hash']}: " . $e->getMessage());
            }
        }

        return $newCount;
    }

    /**
     * Fetch outgoing transactions from the appropriate service
     */
    protected function fetchOutgoingTransactions(
        CurrencyChainEnum $chain,
        string $address,
        ?string $currencySymbol,
        ?int $afterBlock,
        TronScanService $tronScanService,
        EtherScanService $etherScanService,
        BlockchairService $blockchairService
    ): array {
        return match ($chain) {
            CurrencyChainEnum::TRC20 => $tronScanService->getOutgoingTransactions($currencySymbol, $address, $afterBlock),
            CurrencyChainEnum::ERC20 => $etherScanService->getOutgoingTransactions($currencySymbol, $address, $afterBlock),
            CurrencyChainEnum::BSC   => $etherScanService->getOutgoingTransactions($currencySymbol, $address, $afterBlock, 56),
            CurrencyChainEnum::BTC   => $blockchairService->getOutgoingTransactions('BTC', $address, $afterBlock),
            CurrencyChainEnum::DOGE  => $blockchairService->getOutgoingTransactions('DOGE', $address, $afterBlock),
            CurrencyChainEnum::LTC   => $blockchairService->getOutgoingTransactions('LTC', $address, $afterBlock),
            CurrencyChainEnum::DASH  => $blockchairService->getOutgoingTransactions('DASH', $address, $afterBlock),
            default => ['error' => 'Unsupported chain', 'transactions' => []],
        };
    }

    /**
     * Get native currency for a chain
     */
    protected function getNativeCurrency(CurrencyChainEnum $chain): ?Currency
    {
        $symbol = match ($chain) {
            CurrencyChainEnum::TRC20 => 'TRX',
            CurrencyChainEnum::ERC20 => 'ETH',
            CurrencyChainEnum::BSC => 'BNB',
            CurrencyChainEnum::BTC => 'BTC',
            CurrencyChainEnum::DOGE => 'DOGE',
            CurrencyChainEnum::LTC => 'LTC',
            CurrencyChainEnum::DASH => 'DASH',
            default => null,
        };

        return $symbol ? Currency::where('symbol', $symbol)->first() : null;
    }

    /**
     * Update sync progress in cache
     */
    protected function updateProgress(int $percentage, string $message, string $status, array $data = []): void
    {
        $progressData = [
            'sync_id' => $this->syncId,
            'currency_symbol' => $this->currencySymbol,
            'currency_chain_id' => $this->currencyChainId,
            'percentage' => $percentage,
            'message' => $message,
            'status' => $status, // 'processing', 'completed', 'error'
            'updated_at' => now()->toIso8601String(),
            'data' => $data,
        ];

        // Store in cache for 1 hour
        Cache::put("sync_progress:{$this->syncId}", $progressData, 3600);
    }
}
