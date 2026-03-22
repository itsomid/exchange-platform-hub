<?php

namespace App\Console\Commands;

use App\Enums\CurrencyChainEnum;
use App\Enums\DepositStatusEnum;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\Deposit;
use App\Models\HdWalletOutgoingTransaction;

use App\Services\NodeProviders\BlockchairService;
use App\Services\NodeProviders\BscScanService;
use App\Services\NodeProviders\EtherScanService;
use App\Services\NodeProviders\TronScanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncHdWalletOutgoingTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hd-wallet:sync-outgoing-transactions
                            {--coin= : The currency symbol (e.g., USDT, TRX, ETH)}
                            {--chain= : The chain type (e.g., TRC20, ERC20, BSC, BTC)}
                            {--address= : Sync only for a specific address}
                            {--delay=500 : Delay between API calls in milliseconds}
                            {--limit= : Limit number of addresses to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync outgoing transactions from blockchain for HD wallet addresses';

    /**
     * Services
     */
    protected TronScanService $tronScanService;
    protected EtherScanService $etherScanService;
    protected BscScanService $bscScanService;
    protected BlockchairService $blockchairService;

    public function __construct(
        TronScanService $tronScanService,
        EtherScanService $etherScanService,
        BscScanService $bscScanService,
        BlockchairService $blockchairService
    ) {
        parent::__construct();
        $this->tronScanService = $tronScanService;
        $this->etherScanService = $etherScanService;
        $this->bscScanService = $bscScanService;
        $this->blockchairService = $blockchairService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $coinSymbol = $this->option('coin');
        $chainType = $this->option('chain');
        $specificAddress = $this->option('address');
        $delay = (int) $this->option('delay');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        // Validate chain
        if (!$chainType) {
            $this->error('The --chain option is required.');
            return 1;
        }

        // Find currency if specified
        $currency = null;
        if ($coinSymbol) {
            $currency = Currency::where('symbol', $coinSymbol)->first();
            if (!$currency) {
                $this->error("Currency '{$coinSymbol}' not found.");
                return 1;
            }
        }

        $chainQuery = CurrencyChain::query()->where('chain', $chainType);
        if ($currency) {
            $chainQuery->where('currency_id', $currency->id);
        } else {
            $chainQuery->where('is_base_coin', true);
        }

        $chain = $chainQuery->first();
        if (!$chain) {
            $suffix = $currency ? " for currency '{$currency->symbol}'" : '';
            $this->error("Chain '{$chainType}'{$suffix} not found.");
            return 1;
        }

        $this->info("Starting outgoing transactions sync...");
        $this->info("Chain: {$chain->chain->value}");
        if ($currency) {
            $this->info("Currency: {$currency->symbol}");
        }
        $this->info("Delay: {$delay}ms");
        $this->newLine();

        // Get addresses to process
        $addresses = $this->getAddressesToProcess($chain, $currency, $specificAddress, $limit);

        if ($addresses->isEmpty()) {
            $this->warn('No addresses found to process.');
            return 0;
        }

        $this->info("Found {$addresses->count()} addresses to process.");
        $this->newLine();

        $bar = $this->output->createProgressBar($addresses->count());
        $bar->start();

        $totalNewTransactions = 0;
        $errors = [];

        foreach ($addresses as $addressRecord) {
            $this->warn("{$addressRecord->address}");
            try {
                $newCount = $this->syncAddressTransactions(
                    $addressRecord,
                    $chain,
                    $currency
                );
                $totalNewTransactions += $newCount;
            } catch (\Exception $e) {
                $errors[] = [
                    'address' => $addressRecord->address,
                    'error' => $e->getMessage()
                ];
                Log::error("Error syncing outgoing transactions for {$addressRecord->address}: " . $e->getMessage());
            }

            $bar->advance();

            // Delay between API calls
            usleep($delay * 1000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync completed!");
        $this->info("New transactions saved: {$totalNewTransactions}");

        if (!empty($errors)) {
            $this->newLine();
            $this->warn("Errors occurred for " . count($errors) . " addresses:");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->error("  - {$error['address']}: {$error['error']}");
            }
            if (count($errors) > 10) {
                $this->warn("  ... and " . (count($errors) - 10) . " more errors");
            }
        }

        return 0;
    }

    /**
     * Get addresses to process based on filters
     * Only get addresses that have confirmed deposits (real on-chain activity)
     */
    protected function getAddressesToProcess($chain, $currency, $specificAddress, $limit)
    {
        // Get addresses from deposits table - only addresses with confirmed deposits
        $query = Deposit::query()
            ->where('status', DepositStatusEnum::CONFIRMED)
            ->whereNotNull('transaction_hash') // Only real on-chain deposits
            ->where('currency_chain_id', $chain->id)
            ->whereNotNull('address');

        if ($currency) {
            $query->where('currency_symbol', $currency->symbol);
        }

        if ($specificAddress) {
            $query->where('address', $specificAddress);
        }

        // Select unique addresses with user_id
        $query->select([
            'address',
            'user_id',
            DB::raw("'{$chain->chain->value}' as chain"),
        ])->distinct('address');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Sync outgoing transactions for a single address
     */
    protected function syncAddressTransactions($addressRecord, $chain, $currency): int
    {
        // $chain->chain is already a CurrencyChainEnum (cast in model)
        $chainEnum = $chain->chain;

        if (!$chainEnum instanceof CurrencyChainEnum) {
            return 0;
        }

        // Get the latest synced block number for this address
        $latestBlockNumber = HdWalletOutgoingTransaction::where('from_address', $addressRecord->address)
            ->where('currency_chain_id', $chain->id)
            ->max('block_number');

        // Fetch transactions from blockchain
        $result = $this->fetchOutgoingTransactions(
            $chainEnum,
            $addressRecord->address,
            $currency?->symbol,
            $latestBlockNumber
        );

        if (isset($result['error'])) {
            throw new \Exception($result['error'] . ': ' . ($result['details'] ?? ''));
        }

        $transactions = $result['transactions'] ?? [];
        $newCount = 0;

        foreach ($transactions as $tx) {
            // Skip if already exists
            if (HdWalletOutgoingTransaction::where('transaction_hash', $tx['transaction_hash'])->exists()) {
                continue;
            }

            // Determine currency for this transaction
            $txCurrency = $currency;
            if (!$txCurrency && isset($tx['currency_symbol'])) {
                $txCurrency = Currency::where('symbol', $tx['currency_symbol'])->first();
            }

            // For native coins, get the default currency
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
                // Log duplicate or other errors but continue
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
        ?int $afterBlock
    ): array {
        return match ($chain) {
            CurrencyChainEnum::TRC20 => $this->tronScanService->getOutgoingTransactions($currencySymbol, $address, $afterBlock),
            CurrencyChainEnum::ERC20 => $this->etherScanService->getOutgoingTransactions($currencySymbol, $address, $afterBlock),
            CurrencyChainEnum::BSC => $this->bscScanService->getOutgoingTransactions($currencySymbol, $address, $afterBlock),
            CurrencyChainEnum::BTC => $this->blockchairService->getOutgoingTransactions('BTC', $address, $afterBlock),
            CurrencyChainEnum::DOGE => $this->blockchairService->getOutgoingTransactions('DOGE', $address, $afterBlock),
            CurrencyChainEnum::LTC => $this->blockchairService->getOutgoingTransactions('LTC', $address, $afterBlock),
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
            default => null,
        };

        return $symbol ? Currency::where('symbol', $symbol)->first() : null;
    }
}
