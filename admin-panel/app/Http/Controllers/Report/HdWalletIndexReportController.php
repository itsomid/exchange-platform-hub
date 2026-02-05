<?php

namespace App\Http\Controllers\Report;

use App\Enums\CurrencyChainEnum;
use App\Enums\DepositStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\Deposit;
use App\Models\HdWalletOutgoingTransaction;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Services\NodeProviders\BlockchairService;
use App\Services\NodeProviders\BscScanService;
use App\Services\NodeProviders\EtherScanService;
use App\Services\NodeProviders\TronScanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
