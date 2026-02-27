<?php

namespace App\Http\Controllers\Report;

use App\Enums\CurrencyChainEnum;
use App\Enums\DepositStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\SyncHdWalletOutgoingTransactionsJob;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\ExchangePrice;
use App\Models\Deposit;
use App\Models\HdWalletOutgoingTransaction;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Services\NodeProviders\BlockchairService;
use App\Services\NodeProviders\BscScanService;
use App\Services\NodeProviders\EtherScanService;
use App\Services\NodeProviders\TronScanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HdWalletIndexReportController extends Controller
{
    /**
     * Display the HD Wallet Index Balance Report page.
     */
    public function index(Request $request)
    {
        // Get all currencies that have chains (for the currency selection modal)
        $currencies = Currency::whereHas('chains')->orderBy('symbol')->get();

        // Get all currency chains with their currency info for the modal list
        $currencyChainsList = CurrencyChain::with('currency')
            ->whereNotNull('chain')
            ->whereHas('currency')
            ->get()
            ->sortBy(function ($chain) {
                return $chain->currency->symbol . '-' . $chain->chain_name;
            });

        return view('dashboard.report.hd-wallet-index-report', [
            'currencies' => $currencies,
            'currencyChainsList' => $currencyChainsList,
        ]);
    }

    /**
     * Get balance data for a specific currency and chain via AJAX.
     * Balance = Deposits - Outgoing Transactions (if include_withdrawals is true)
     */
    public function getBalanceData(Request $request)
    {
        $request->validate([
            'currency_symbol' => 'required|string|exists:currencies,symbol',
            'currency_chain_id' => 'nullable|integer|exists:currency_chains,id',
            'min_balance' => 'nullable|numeric|min:0',
            'per_page' => 'nullable|integer|min:10|max:100',
            'include_withdrawals' => 'nullable|boolean',
        ]);

        $currencySymbol = $request->currency_symbol;
        $currencyChainId = $request->currency_chain_id;

        $minBalance = $request->min_balance ?? 0;
        $perPage = $request->per_page ?? 20;
        $includeWithdrawals = $request->boolean('include_withdrawals', true);

        if ($includeWithdrawals) {
            // Use raw SQL with proper parameter binding for join query
            $chainCondition = $currencyChainId ? "AND currency_chain_id = ?" : "";
            $chainBindings = $currencyChainId ? [$currencyChainId] : [];

            $sql = "
                SELECT
                    d.user_id as hd_wallet_index,
                    d.currency_symbol,
                    d.currency_chain_id,
                    d.total_deposits,
                    COALESCE(o.total_outgoing, 0) as total_outgoing,
                    (d.total_deposits - COALESCE(o.total_outgoing, 0)) as total_balance,
                    d.deposit_count,
                    COALESCE(o.outgoing_count, 0) as outgoing_count,
                    d.last_deposit_at,
                    d.first_deposit_at
                FROM (
                    SELECT
                        user_id,
                        currency_symbol,
                        currency_chain_id,
                        SUM(amount) as total_deposits,
                        COUNT(*) as deposit_count,
                        MAX(created_at) as last_deposit_at,
                        MIN(created_at) as first_deposit_at
                    FROM deposits
                    WHERE status = ?
                        AND transaction_hash IS NOT NULL
                        AND currency_symbol = ?
                        {$chainCondition}
                    GROUP BY user_id, currency_symbol, currency_chain_id
                ) as d
                LEFT JOIN (
                    SELECT
                        user_id,
                        currency_symbol,
                        currency_chain_id,
                        SUM(amount) as total_outgoing,
                        COUNT(*) as outgoing_count
                    FROM hd_wallet_outgoing_transactions
                    WHERE currency_symbol = ?
                        {$chainCondition}
                    GROUP BY user_id, currency_symbol, currency_chain_id
                ) as o ON d.user_id = o.user_id
                    AND d.currency_symbol = o.currency_symbol
                    AND d.currency_chain_id = o.currency_chain_id
                HAVING total_balance > ?
                ORDER BY total_balance DESC
            ";

            $bindings = array_merge(
                [DepositStatusEnum::CONFIRMED->value, $currencySymbol],
                $chainBindings,
                [$currencySymbol],
                $chainBindings,
                [$minBalance]
            );

            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as count_table";
            $totalCount = DB::selectOne($countSql, $bindings)->total;

            // Add pagination
            $offset = ($request->input('page', 1) - 1) * $perPage;
            $paginatedSql = $sql . " LIMIT ? OFFSET ?";
            $paginatedBindings = array_merge($bindings, [$perPage, $offset]);

            $results = DB::select($paginatedSql, $paginatedBindings);

            $balances = new \Illuminate\Pagination\LengthAwarePaginator(
                collect($results),
                $totalCount,
                $perPage,
                $request->input('page', 1),
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            // Original behavior - only deposits (no withdrawal calculation)
            $depositsSubQuery = Deposit::select(
                'user_id',
                'currency_symbol',
                'currency_chain_id',
                DB::raw('SUM(amount) as total_deposits'),
                DB::raw('COUNT(*) as deposit_count'),
                DB::raw('MAX(created_at) as last_deposit_at'),
                DB::raw('MIN(created_at) as first_deposit_at')
            )
                ->where('status', DepositStatusEnum::CONFIRMED)
                ->whereNotNull('transaction_hash')
                ->where('currency_symbol', $currencySymbol)
                ->when($currencyChainId, function ($query) use ($currencyChainId) {
                    $query->where('currency_chain_id', $currencyChainId);
                })
                ->groupBy('user_id', 'currency_symbol', 'currency_chain_id');

            $balancesQuery = DB::table(DB::raw("({$depositsSubQuery->toSql()}) as deposits"))
                ->mergeBindings($depositsSubQuery->getQuery())
                ->select([
                    'deposits.user_id as hd_wallet_index',
                    'deposits.currency_symbol',
                    'deposits.currency_chain_id',
                    'deposits.total_deposits as total_balance',
                    'deposits.total_deposits',
                    DB::raw('0 as total_outgoing'),
                    'deposits.deposit_count',
                    DB::raw('0 as outgoing_count'),
                    'deposits.last_deposit_at',
                    'deposits.first_deposit_at',
                ])
                ->having('total_balance', '>', $minBalance)
                ->orderByDesc('total_balance');

            $balances = $balancesQuery->paginate($perPage);
        }

        // Calculate summary totals
        $totalDeposits = Deposit::where('status', DepositStatusEnum::CONFIRMED)
            ->whereNotNull('transaction_hash')
            ->where('currency_symbol', $currencySymbol)
            ->when($currencyChainId, fn($q) => $q->where('currency_chain_id', $currencyChainId))
            ->sum('amount');

        $totalOutgoing = HdWalletOutgoingTransaction::where('currency_symbol', $currencySymbol)
            ->when($currencyChainId, fn($q) => $q->where('currency_chain_id', $currencyChainId))
            ->sum('amount');

        $totalBalance = $includeWithdrawals ? bcsub($totalDeposits, $totalOutgoing, 8) : $totalDeposits;

        $totalIndexCount = Deposit::where('status', DepositStatusEnum::CONFIRMED)
            ->whereNotNull('transaction_hash')
            ->where('currency_symbol', $currencySymbol)
            ->when($currencyChainId, fn($q) => $q->where('currency_chain_id', $currencyChainId))
            ->distinct('user_id')
            ->count('user_id');

        $totalDepositCount = Deposit::where('status', DepositStatusEnum::CONFIRMED)
            ->whereNotNull('transaction_hash')
            ->where('currency_symbol', $currencySymbol)
            ->when($currencyChainId, fn($q) => $q->where('currency_chain_id', $currencyChainId))
            ->count();

        $totalOutgoingCount = HdWalletOutgoingTransaction::where('currency_symbol', $currencySymbol)
            ->when($currencyChainId, fn($q) => $q->where('currency_chain_id', $currencyChainId))
            ->count();

        // Get currency and chain info
        $currency = Currency::where('symbol', $currencySymbol)->first();
        $chain = $currencyChainId ? CurrencyChain::find($currencyChainId) : null;

        // Transform data for response
        $items = collect($balances->items())->map(function ($item) use ($chain, $includeWithdrawals) {
            return [
                'hd_wallet_index' => $item->hd_wallet_index,
                'currency_symbol' => $item->currency_symbol,
                'chain_name' => $chain?->chain_name ?? 'همه شبکه‌ها',
                'total_balance' => formatNumberTrimZeros($item->total_balance),
                'total_balance_raw' => $item->total_balance,
                'total_deposits' => $includeWithdrawals ? formatNumberTrimZeros($item->total_deposits) : null,
                'total_outgoing' => $includeWithdrawals ? formatNumberTrimZeros($item->total_outgoing) : null,
                'deposit_count' => $item->deposit_count,
                'outgoing_count' => $item->outgoing_count ?? 0,
                'last_deposit_at' => $item->last_deposit_at ? \Morilog\Jalali\Jalalian::forge($item->last_deposit_at)->format('Y/m/d H:i') : '-',
                'first_deposit_at' => $item->first_deposit_at ? \Morilog\Jalali\Jalalian::forge($item->first_deposit_at)->format('Y/m/d H:i') : '-',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'current_page' => $balances->currentPage(),
                    'last_page' => $balances->lastPage(),
                    'per_page' => $balances->perPage(),
                    'total' => $balances->total(),
                ],
                'summary' => [
                    'total_balance' => formatNumberTrimZeros($totalBalance),
                    'total_balance_raw' => $totalBalance,
                    'total_deposits' => formatNumberTrimZeros($totalDeposits),
                    'total_outgoing' => formatNumberTrimZeros($totalOutgoing),
                    'total_index_count' => $totalIndexCount,
                    'total_deposit_count' => $totalDepositCount,
                    'total_outgoing_count' => $totalOutgoingCount,
                    'currency_symbol' => $currencySymbol,
                    'currency_name' => $currency?->persian_name ?? $currency?->name ?? $currencySymbol,
                    'chain_name' => $chain?->chain_name ?? 'همه شبکه‌ها',
                    'include_withdrawals' => $includeWithdrawals,
                ],
            ],
        ]);
    }

    /**
     * Get available chains for a specific currency.
     */
    public function getCurrencyChains(Request $request)
    {
        $request->validate([
            'currency_symbol' => 'required|string|exists:currencies,symbol',
        ]);

        $currency = Currency::where('symbol', $request->currency_symbol)->first();

        $chains = CurrencyChain::where('currency_id', $currency->id)
            ->whereNotNull('chain')
            ->get(['id', 'chain_name', 'chain']);

        return response()->json([
            'success' => true,
            'chains' => $chains,
        ]);
    }

    /**
     * Export balance data to Excel.
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'currency_symbol' => 'required|string|exists:currencies,symbol',
            'currency_chain_id' => 'nullable|integer|exists:currency_chains,id',
            'min_balance' => 'nullable|numeric|min:0',
        ]);

        $currencySymbol = $request->currency_symbol;
        $currencyChainId = $request->currency_chain_id;
        $minBalance = $request->min_balance ?? 0;

        // Exclude manual deposits (those without transaction_hash) from export
        $balances = Deposit::select(
            'user_id as hd_wallet_index',
            'currency_symbol',
            'currency_chain_id',
            DB::raw('SUM(amount) as total_balance'),
            DB::raw('COUNT(*) as deposit_count'),
            DB::raw('MAX(created_at) as last_deposit_at'),
            DB::raw('MIN(created_at) as first_deposit_at')
        )
            ->where('status', DepositStatusEnum::CONFIRMED)
            ->whereNotNull('transaction_hash') // Exclude manual/admin deposits
            ->where('currency_symbol', $currencySymbol)
            ->when($currencyChainId, function ($query) use ($currencyChainId) {
                $query->where('currency_chain_id', $currencyChainId);
            })
            ->groupBy('user_id', 'currency_symbol', 'currency_chain_id')
            ->having('total_balance', '>', $minBalance)
            ->orderByDesc('total_balance')
            ->get();

        $chain = $currencyChainId ? CurrencyChain::find($currencyChainId) : null;
        $chainName = $chain?->chain_name ?? 'all-chains';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\HdWalletIndexBalanceExport($balances, $currencySymbol, $chainName),
            "hd-wallet-index-balance-{$currencySymbol}-{$chainName}-" . now()->format('Y-m-d') . ".xlsx"
        );
    }

    /**
     * Map CurrencyChainEnum to HD Wallet Sweeper network name.
     */
    private function mapChainToSweeperNetwork(string $chainValue): ?string
    {
        return match ($chainValue) {
            CurrencyChainEnum::BTC->value => 'bitcoin',
            CurrencyChainEnum::ERC20->value => 'ethereum',
            CurrencyChainEnum::TRC20->value => 'tron',
            CurrencyChainEnum::BSC->value => 'bnb',
            CurrencyChainEnum::DOGE->value => 'dogecoin',
            default => null,
        };
    }

    /**
     * Determine if the currency is native for its chain.
     */
    private function isNativeCoinForChain(string $chainValue, string $currencySymbol): bool
    {
        return match ($chainValue) {
            CurrencyChainEnum::TRC20->value => $currencySymbol === 'TRX',
            CurrencyChainEnum::ERC20->value => $currencySymbol === 'ETH',
            CurrencyChainEnum::BSC->value => $currencySymbol === 'BNB',
            CurrencyChainEnum::BTC->value => $currencySymbol === 'BTC',
            CurrencyChainEnum::DOGE->value => $currencySymbol === 'DOGE',
            default => false,
        };
    }

    /**
     * Sweep selected indices via HD Wallet Sweeper API.
     * Sends selected HD wallet indices to the sweeper to create admin_approval transactions.
     */
    public function sweepSelectedIndices(Request $request)
    {
        $request->validate([
            'indices' => 'required|array|min:1|max:100',
            'indices.*' => 'required|integer|min:0',
            'currency_symbol' => 'required|string|exists:currencies,symbol',
            'currency_chain_id' => 'required|integer|exists:currency_chains,id',
            'wallet_id' => 'required|string|max:50',
            'force' => 'nullable|boolean',
        ]);

        $indices = $request->indices;
        $currencySymbol = $request->currency_symbol;
        $currencyChainId = $request->currency_chain_id;
        $walletId = $request->wallet_id;
        $force = $request->boolean('force', false);

        // Get chain info
        $currencyChain = CurrencyChain::with('currency')->find($currencyChainId);
        if (!$currencyChain) {
            return response()->json([
                'success' => false,
                'error' => 'زنجیره پیدا نشد',
            ], 404);
        }

        // Map chain to sweeper network name
        $chainValue = $currencyChain->chain instanceof CurrencyChainEnum
            ? $currencyChain->chain->value
            : (string) $currencyChain->chain;

        $sweeperNetwork = $this->mapChainToSweeperNetwork($chainValue);
        if (!$sweeperNetwork) {
            return response()->json([
                'success' => false,
                'error' => "شبکه {$chainValue} در سیستم برداشت پشتیبانی نمی‌شود",
            ], 400);
        }

        // Determine coinType (native or token)
        $isNative = $this->isNativeCoinForChain($chainValue, $currencySymbol);
        $coinType = $isNative ? 'native' : 'token';

        // Build request body for sweeper API
        $requestBody = [
            'network' => $sweeperNetwork,
            'walletId' => $walletId,
            'indices' => array_map('intval', $indices),
            'coinType' => $coinType,
            'force' => $force,
        ];

        // Add symbol for token sweeps
        if ($coinType === 'token') {
            $requestBody['symbol'] = $currencySymbol;
        }

        // Call HD Wallet Sweeper API
        $baseUrl = config('sweeper.base_url');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(120)
                ->post("{$baseUrl}/api/admin-panel/sweep-indices", $requestBody);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::channel('hd-wallet')->error('Sweep indices failed:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request' => $requestBody,
                ]);

                // Get error message from sweeper response
                $errorMessage = $response->json('error') ?? 'خطا در ارسال درخواست برداشت';

                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'walletInfo' => $response->json('walletInfo'),
                ], $response->status());
            }

            $responseData = $response->json();

            \Illuminate\Support\Facades\Log::channel('hd-wallet')->info('Sweep indices result:', [
                'request' => $requestBody,
                'summary' => $responseData['data']['summary'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $responseData['data'] ?? [],
                'message' => sprintf(
                    '%d ایندکس با موفقیت به پروسه برداشت ارسال شد (موفق: %d، ناموفق: %d)',
                    count($indices),
                    $responseData['data']['summary']['successful'] ?? 0,
                    $responseData['data']['summary']['failed'] ?? 0,
                ),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::channel('hd-wallet')->error('Sweep indices connection failed:', [
                'exception' => $e->getMessage(),
                'request' => $requestBody,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'سرویس HD Wallet Sweeper در دسترس نیست',
            ], 503);
        } catch (\Exception $e) {
            \Log::channel('hd-wallet')->error('Sweep indices exception:', [
                'exception' => $e->getMessage(),
                'request' => $requestBody,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'خطای داخلی: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fund selected indices with native coin from index 1 via HD Wallet Sweeper API.
     * Sends native coin from index 1 to each selected address for gas fee coverage.
     */
    public function fundSelectedIndices(Request $request)
    {
        $request->validate([
            'indices' => 'required|array|min:1|max:100',
            'indices.*' => 'required|integer|min:0',
            'currency_chain_id' => 'required|integer|exists:currency_chains,id',
            'wallet_id' => 'required|string|max:50',
            'amount' => 'required|numeric|gt:0',
        ]);

        $indices = $request->indices;
        $currencyChainId = $request->currency_chain_id;
        $walletId = $request->wallet_id;
        $amount = $request->amount;

        // Get chain info
        $currencyChain = CurrencyChain::with('currency')->find($currencyChainId);
        if (!$currencyChain) {
            return response()->json([
                'success' => false,
                'error' => 'زنجیره پیدا نشد',
            ], 404);
        }

        // Map chain to sweeper network name
        $chainValue = $currencyChain->chain instanceof CurrencyChainEnum
            ? $currencyChain->chain->value
            : (string) $currencyChain->chain;

        $sweeperNetwork = $this->mapChainToSweeperNetwork($chainValue);
        if (!$sweeperNetwork) {
            return response()->json([
                'success' => false,
                'error' => "شبکه {$chainValue} در سیستم پشتیبانی نمی‌شود",
            ], 400);
        }

        // Only EVM and Tron networks support gas funding
        if (!in_array($sweeperNetwork, ['ethereum', 'tron', 'bnb'])) {
            return response()->json([
                'success' => false,
                'error' => 'واریز گس فقط برای شبکه‌های ERC20، TRC20 و BSC امکان‌پذیر است',
            ], 400);
        }

        // Build request body for sweeper API
        $requestBody = [
            'network' => $sweeperNetwork,
            'walletId' => $walletId,
            'indices' => array_map('intval', $indices),
            'amount' => (string) $amount,
            'maxConcurrent' => 1,
        ];

        // Call HD Wallet Sweeper API
        $baseUrl = config('sweeper.base_url');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(300)
                ->post("{$baseUrl}/api/admin-panel/fund-indices", $requestBody);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::channel('hd-wallet')->error('Fund indices failed:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request' => $requestBody,
                ]);

                $responseData = $response->json();
                $errorMessage = $responseData['error'] ?? 'خطا در ارسال درخواست واریز گس';

                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'data' => $responseData['data'] ?? null,
                    'walletInfo' => $responseData['walletInfo'] ?? null,
                ], $response->status());
            }

            $responseData = $response->json();

            \Illuminate\Support\Facades\Log::channel('hd-wallet')->info('Fund indices result:', [
                'request' => $requestBody,
                'summary' => $responseData['data']['summary'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $responseData['data'] ?? [],
                'message' => sprintf(
                    'واریز گس: %d موفق، %d ناموفق از %d درخواست',
                    $responseData['data']['summary']['successful'] ?? 0,
                    $responseData['data']['summary']['failed'] ?? 0,
                    count($indices),
                ),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::channel('hd-wallet')->error('Fund indices connection failed:', [
                'exception' => $e->getMessage(),
                'request' => $requestBody,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'سرویس HD Wallet Sweeper در دسترس نیست',
            ], 503);
        } catch (\Exception $e) {
            \Log::channel('hd-wallet')->error('Fund indices exception:', [
                'exception' => $e->getMessage(),
                'request' => $requestBody,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'خطای داخلی: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of wallets from HD Wallet Sweeper service.
     * Proxies the request to the sweeper's admin-panel wallets endpoint.
     */
    public function getSweeperWallets(Request $request)
    {
        $baseUrl = config('sweeper.base_url');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->get("{$baseUrl}/api/admin-panel/wallets", [
                    'status' => $request->query('status', 'active'),
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'خطا در دریافت لیست والت‌ها',
                    'details' => $response->body(),
                ], $response->status());
            }

            return response()->json($response->json());
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'سرویس HD Wallet Sweeper در دسترس نیست',
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'خطای داخلی: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Query blockchain for real-time balance of a wallet address.
     */
    public function queryBlockchainBalance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'currency_symbol' => 'required|string|exists:currencies,symbol',
            'currency_chain_id' => 'required|integer|exists:currency_chains,id',
        ]);

        $userId = $request->user_id;
        $currencySymbol = $request->currency_symbol;
        $currencyChainId = $request->currency_chain_id;

        // Get the chain info
        $currencyChain = CurrencyChain::find($currencyChainId);
        if (!$currencyChain) {
            return response()->json([
                'success' => false,
                'error' => 'زنجیره پیدا نشد',
            ]);
        }

        // Get user's wallet for this currency
        $wallet = Wallet::where('user_id', $userId)
            ->where('currency_symbol', $currencySymbol)
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'error' => 'کیف پول کاربر یافت نشد',
            ]);
        }

        // Get the wallet chain address
        $walletChain = WalletChain::where('wallet_id', $wallet->id)
            ->where('currency_chain', $currencyChain->chain)
            ->first();

        if (!$walletChain || !$walletChain->address) {
            return response()->json([
                'success' => false,
                'error' => 'آدرس کیف پول برای این شبکه یافت نشد',
            ]);
        }

        $address = $walletChain->address;
        $chain = $currencyChain->chain;

        // Select the appropriate service based on chain
        try {
            $balance = $this->getBalanceFromBlockchain($currencySymbol, $chain, $address);

            if (isset($balance['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $balance['error'],
                    'details' => $balance['details'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'address' => $address,
                    'balance' => formatNumberTrimZeros($balance['amount']),
                    'balance_raw' => $balance['amount'],
                    'currency_symbol' => $currencySymbol,
                    'chain_name' => $currencyChain->chain_name,
                    'explorer_url' => $walletChain->explorer_address_url,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'خطا در استعلام از شبکه: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get balance from blockchain using appropriate service.
     */
    private function getBalanceFromBlockchain(string $currencySymbol, $chain, string $address): array
    {
        // Convert chain to string if it's an enum
        $chainValue = $chain instanceof CurrencyChainEnum ? $chain->value : (string) $chain;

        switch ($chainValue) {
            case CurrencyChainEnum::TRC20->value:
                $service = new TronScanService();
                return $service->getBalance($currencySymbol, $address);

            case CurrencyChainEnum::ERC20->value:
                $service = new EtherScanService();
                return $service->getBalance($currencySymbol, $address);

            case CurrencyChainEnum::BSC->value:
                $service = new BscScanService();
                return $service->getBalance($currencySymbol, $address);

            case CurrencyChainEnum::BTC->value:
            case CurrencyChainEnum::DOGE->value:
            case CurrencyChainEnum::LTC->value:
                $service = new BlockchairService();
                return $service->getBalance($currencySymbol, $address);

            default:
                return [
                    'error' => "استعلام موجودی برای شبکه {$chainValue} پشتیبانی نمی‌شود",
                ];
        }
    }

    /**
     * Start sync process for outgoing transactions.
     */
    public function startSync(Request $request)
    {
        $request->validate([
            'currency_symbol' => 'required|string|exists:currencies,symbol',
            'currency_chain_id' => 'required|integer|exists:currency_chains,id',
            'delay' => 'nullable|integer|min:100|max:5000',
        ]);

        $currencySymbol = $request->currency_symbol;
        $currencyChainId = $request->currency_chain_id;
        $delay = $request->delay ?? 500;

        // Generate unique sync ID
        $syncId = 'sync_' . Str::uuid();

        // Dispatch job
        SyncHdWalletOutgoingTransactionsJob::dispatch($syncId, $currencySymbol, $currencyChainId, $delay);

        // Store initial progress
        Cache::put("sync_progress:{$syncId}", [
            'sync_id' => $syncId,
            'currency_symbol' => $currencySymbol,
            'currency_chain_id' => $currencyChainId,
            'percentage' => 0,
            'message' => 'در حال شروع...',
            'status' => 'starting',
            'updated_at' => now()->toIso8601String(),
            'data' => [],
        ], 3600);

        return response()->json([
            'success' => true,
            'sync_id' => $syncId,
            'message' => 'پروسه همگام‌سازی شروع شد',
        ]);
    }

    /**
     * Get sync progress.
     */
    public function getSyncProgress(Request $request, string $syncId)
    {
        $progress = Cache::get("sync_progress:{$syncId}");

        if (!$progress) {
            return response()->json([
                'success' => false,
                'error' => 'اطلاعات همگام‌سازی یافت نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Estimate gas funding cost for token transfers (ERC20/BSC/TRC20).
     * Returns the estimated gas cost per address and total cost with USD price.
     */
    public function estimateGasFunding(Request $request)
    {
        $request->validate([
            'currency_chain_id' => 'required|integer|exists:currency_chains,id',
            'indices_count' => 'required|integer|min:1|max:100',
        ]);

        $currencyChainId = $request->currency_chain_id;
        $indicesCount = $request->indices_count;

        // Get chain info
        $currencyChain = CurrencyChain::with('currency')->find($currencyChainId);
        if (!$currencyChain) {
            return response()->json([
                'success' => false,
                'error' => 'زنجیره پیدا نشد',
            ], 404);
        }

        $chainValue = $currencyChain->chain instanceof CurrencyChainEnum
            ? $currencyChain->chain->value
            : (string) $currencyChain->chain;

        // Only EVM and Tron networks need gas funding
        if (!in_array($chainValue, ['ERC20', 'BSC', 'TRC20'])) {
            return response()->json([
                'success' => false,
                'error' => 'تخمین گس فقط برای شبکه‌های ERC20, BSC و TRC20 امکان‌پذیر است',
            ], 400);
        }

        // Native coin symbols
        $nativeCoinMap = [
            'ERC20' => 'ETH',
            'BSC' => 'BNB',
            'TRC20' => 'TRX',
        ];
        $nativeCoin = $nativeCoinMap[$chainValue];

        try {
            $gasEstimate = null;

            // Get gas estimation based on network
            if ($chainValue === 'ERC20') {
                $service = new EtherScanService();
                $gasEstimate = $service->estimateTokenTransferGasCost($currencyChain->currency->symbol, 'SafeGasPrice');
            } elseif ($chainValue === 'BSC') {
                $service = new BscScanService();
                $gasEstimate = $service->estimateTokenTransferGasCost($currencyChain->currency->symbol, 'SafeGasPrice');
            } elseif ($chainValue === 'TRC20') {
                // For TRC20, use approximate values (TronGrid doesn't have simple gas oracle)
                // Typical TRC20 transfer consumes ~35k energy, ~270 bandwidth
                // At current network prices (~140 SUN/energy), cost is about 5 TRX
                $gasEstimate = [
                    'gasPrice' => '140', // SUN per energy unit (approximate)
                    'gasLimit' => '35000', // Energy units (approximate)
                    'totalCostTRX' => '12', // Approximate TRX needed
                    'nativeSymbol' => 'TRX',
                    'note' => 'تخمینی برای TRC20 (حدود ۵ ترون برای یک انتقال توکن)',
                ];
            }

            if (isset($gasEstimate['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'خطا در دریافت اطلاعات گس: ' . $gasEstimate['error'],
                ], 500);
            }

            // Get native coin price in USD
            // First try from API response (if available), then fallback to database
            $nativeCoinPrice = null;

            try {
                $exchangePrice = ExchangePrice::whereHas('market.baseCurrency', function ($query) use ($nativeCoin) {
                    $query->where('symbol', $nativeCoin);
                })
                    ->where('exchange_id', 1) // Main exchange
                    ->select('id', 'price')
                    ->first();

                if ($exchangePrice) {
                    $nativeCoinPrice = (float) $exchangePrice->price;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to get native coin price', ['error' => $e->getMessage()]);
            }


            // Calculate costs
            $costPerAddress = $chainValue === 'TRC20'
                ? $gasEstimate['totalCostTRX']
                : ($gasEstimate['totalCostETH'] ?? $gasEstimate['totalCostBNB'] ?? '0');

            $totalCostNative = bcmul($costPerAddress, (string) $indicesCount, 18);
            $totalCostNative = rtrim(rtrim($totalCostNative, '0'), '.');

            $totalCostUSD = null;
            if ($nativeCoinPrice) {
                $totalCostUSD = bcmul($totalCostNative, (string) $nativeCoinPrice, 8);
            }

            // Prepare all levels data
            $allLevelsData = [];
            if (isset($gasEstimate['allLevels'])) {
                foreach ($gasEstimate['allLevels'] as $level => $data) {
                    $levelCostPerAddress = $data['cost'];
                    $levelTotalCost = bcmul($levelCostPerAddress, (string) $indicesCount, 18);
                    $levelTotalCost = rtrim(rtrim($levelTotalCost, '0'), '.');
                    $levelTotalCostUSD = $nativeCoinPrice ? bcmul($levelTotalCost, (string) $nativeCoinPrice, 8) : null;

                    $allLevelsData[$level] = [
                        'gwei' => $data['gwei'],
                        'costPerAddress' => $levelCostPerAddress,
                        'totalCost' => $levelTotalCost,
                        'totalCostUSD' => $levelTotalCostUSD,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chainName' => $currencyChain->chain_name,
                    'chainValue' => $chainValue,
                    'nativeSymbol' => $nativeCoin,
                    'gasPrice' => $gasEstimate['gasPrice'] ?? null,
                    'gasLimit' => $gasEstimate['gasLimit'] ?? null,
                    'costPerAddress' => $costPerAddress,
                    'costPerAddressFormatted' => $costPerAddress . ' ' . $nativeCoin,
                    'indicesCount' => $indicesCount,
                    'totalCostNative' => $totalCostNative,
                    'totalCostNativeFormatted' => $totalCostNative . ' ' . $nativeCoin,
                    'nativeCoinPriceUSD' => $nativeCoinPrice,
                    'totalCostUSD' => $totalCostUSD,
                    'totalCostUSDFormatted' => $totalCostUSD ? '$' . rtrim(rtrim(number_format($totalCostUSD, 8, '.', ','), '0'), '.') : null,
                    'note' => $gasEstimate['note'] ?? null,
                    'recommendation' => "توصیه می‌شود حداقل " . ($allLevelsData['SafeGasPrice']['costPerAddress'] ?? $costPerAddress) . " {$nativeCoin} به هر آدرس واریز کنید",
                    'allLevels' => $allLevelsData,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to estimate gas funding', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'خطا در تخمین هزینه گس',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
