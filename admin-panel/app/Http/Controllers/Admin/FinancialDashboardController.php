<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepositStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Enums\StockContractStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Exports\StockPurchaseExport;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\SpotTrade;
use App\Models\StockContract;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class FinancialDashboardController extends Controller
{
    protected $exchangeUserId;

    public function __construct()
    {
        $this->exchangeUserId = config('bitexroom.user_id', 1);
    }
    public function index()
    {
        return view('dashboard.financial.index');
    }

    public function getTradeStats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // --- Today Trade Volume (or custom range) ---
        $todayStart = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
        $todayEnd = $endDate ? Carbon::parse($endDate)->endOfDay() : now();

        $todaySpotVolume = SpotTrade::whereBetween('created_at', [$todayStart, $todayEnd])
            ->with('market.quoteCurrency')
            ->get()
            ->sum(function ($trade) {
                return $trade->quantity * $trade->price * ($trade->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $todayOTCVolume = OTCOrder::whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', 'success')
            ->with('market.quoteCurrency')
            ->get()
            ->sum(function ($order) {
                return $order->quantity * $order->price * ($order->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $todayTradeVolume = $todaySpotVolume + $todayOTCVolume;

        // --- All Time Trade Volume ---
        $allTimeSpotVolume = SpotTrade::with('market.quoteCurrency')
            ->get()
            ->sum(function ($trade) {
                return $trade->quantity * $trade->price * ($trade->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $allTimeOTCVolume = OTCOrder::where('status', 'success')
            ->with('market.quoteCurrency')
            ->get()
            ->sum(function ($order) {
                return $order->quantity * $order->price * ($order->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $allTimeTradeVolume = $allTimeSpotVolume + $allTimeOTCVolume;

        // --- Number of Trades Today (or custom date) ---
        $tradeDate = $request->input('trade_date');
        $tradeDayStart = $tradeDate ? Carbon::parse($tradeDate)->startOfDay() : now()->startOfDay();
        $tradeDayEnd = $tradeDate ? Carbon::parse($tradeDate)->endOfDay() : now();

        $todaySpotCount = SpotTrade::whereBetween('created_at', [$tradeDayStart, $tradeDayEnd])->count();
        $todayOTCCount = OTCOrder::whereBetween('created_at', [$tradeDayStart, $tradeDayEnd])
            ->where('status', 'success')
            ->count();
        $todayTradeCount = $todaySpotCount + $todayOTCCount;

        // --- All Time Trade Count ---
        $allTimeSpotCount = SpotTrade::count();
        $allTimeOTCCount = OTCOrder::where('status', 'success')->count();
        $allTimeTradeCount = $allTimeSpotCount + $allTimeOTCCount;

        return response()->json([
            'todayTradeVolume' => formatNumberTrimZeros($todayTradeVolume, 2),
            'todaySpotVolume' => formatNumberTrimZeros($todaySpotVolume, 2),
            'todayOTCVolume' => formatNumberTrimZeros($todayOTCVolume, 2),
            'allTimeTradeVolume' => formatNumberTrimZeros($allTimeTradeVolume, 2),
            'allTimeSpotVolume' => formatNumberTrimZeros($allTimeSpotVolume, 2),
            'allTimeOTCVolume' => formatNumberTrimZeros($allTimeOTCVolume, 2),
            'todayTradeCount' => number_format($todayTradeCount),
            'todaySpotCount' => number_format($todaySpotCount),
            'todayOTCCount' => number_format($todayOTCCount),
            'allTimeTradeCount' => number_format($allTimeTradeCount),
            'allTimeSpotCount' => number_format($allTimeSpotCount),
            'allTimeOTCCount' => number_format($allTimeOTCCount),
        ]);
    }

    public function getRevenueStats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // --- Commission Earned Today (or custom range) ---
        $todayStart = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
        $todayEnd = $endDate ? Carbon::parse($endDate)->endOfDay() : now();

        $todayCommission = $this->getCommissionSum($todayStart, $todayEnd);
        $todayOTCCommission = $this->getCommissionSum($todayStart, $todayEnd, TransactionSubTypeEnum::OTC);
        $todaySpotCommission = $this->getCommissionSum($todayStart, $todayEnd, TransactionSubTypeEnum::SPOT);

        // --- All Time Commission ---
        $allTimeCommission = $this->getCommissionSum();
        $allTimeOTCCommission = $this->getCommissionSum(null, null, TransactionSubTypeEnum::OTC);
        $allTimeSpotCommission = $this->getCommissionSum(null, null, TransactionSubTypeEnum::SPOT);

        return response()->json([
            'todayCommission' => formatNumberTrimZeros($todayCommission, 2),
            'todayOTCCommission' => formatNumberTrimZeros($todayOTCCommission, 2),
            'todaySpotCommission' => formatNumberTrimZeros($todaySpotCommission, 2),
            'allTimeCommission' => formatNumberTrimZeros($allTimeCommission, 2),
            'allTimeOTCCommission' => formatNumberTrimZeros($allTimeOTCCommission, 2),
            'allTimeSpotCommission' => formatNumberTrimZeros($allTimeSpotCommission, 2),
        ]);
    }

    private function getCommissionSum(?Carbon $startDate = null, ?Carbon $endDate = null, ?TransactionSubTypeEnum $subtype = null): float
    {
        $query = Transaction::where('type', TransactionTypeEnum::FEE);

        if ($subtype) {
            $query->where('subtype', $subtype);
        } else {
            $query->whereIn('subtype', [TransactionSubTypeEnum::OTC, TransactionSubTypeEnum::SPOT]);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return (float) $query->selectRaw('SUM(ABS(amount) * coin_price) as total')->value('total') ?? 0;
    }

    public function getAssetStats()
    {
        $data = Cache::remember('financial_dashboard_assets', 30, function () {
            $currencies = Currency::all()->keyBy('symbol');

            // --- Total Exchange Wallet Balance (local balances in DB) ---
            $exchangeWallets = Wallet::where('user_id', $this->exchangeUserId)
                ->get();

            $totalExchangeBalance = $exchangeWallets->sum(function ($wallet) use ($currencies) {
                $currency = $currencies->get($wallet->currency_symbol);
                return ($wallet->balance + $wallet->locked_balance) * ($currency?->exchange_price ?? 1);
            });

            // --- Hot Wallet Balance (on-chain balances from cache) ---
            $hotWalletBalances = Cache::get('wallet_balances', []);
            $totalHotWalletBalance = 0;

            foreach ($hotWalletBalances as $symbol => $chains) {
                $currency = $currencies->get($symbol);
                $exchangePrice = $currency?->exchange_price ?? 1;

                foreach ($chains as $chain => $amount) {
                    $totalHotWalletBalance += (float) $amount * $exchangePrice;
                }
            }

            // --- User Liabilities (sum of all user wallet balances) ---
            $totalUserLiabilities = Wallet::where('user_id', '!=', $this->exchangeUserId)
                ->get()
                ->sum(function ($wallet) use ($currencies) {
                    $currency = $currencies->get($wallet->currency_symbol);
                    return ($wallet->balance + $wallet->locked_balance) * ($currency?->exchange_price ?? 1);
                });

            // --- Net Exchange Assets ---
            $netExchangeAssets = $totalExchangeBalance - $totalUserLiabilities;

            return [
                'totalExchangeBalance' => $totalExchangeBalance,
                'totalHotWalletBalance' => $totalHotWalletBalance,
                'totalUserLiabilities' => $totalUserLiabilities,
                'netExchangeAssets' => $netExchangeAssets,
            ];
        });

        return response()->json([
            'totalExchangeBalance' => formatNumberTrimZeros($data['totalExchangeBalance'], 2),
            'totalHotWalletBalance' => formatNumberTrimZeros($data['totalHotWalletBalance'], 2),
            'totalUserLiabilities' => formatNumberTrimZeros($data['totalUserLiabilities'], 2),
            'netExchangeAssets' => formatNumberTrimZeros($data['netExchangeAssets'], 2),
            'isNetNegative' => $data['netExchangeAssets'] < 0,
        ]);
    }

    public function getLiabilityStats()
    {
        $data = Cache::remember('financial_dashboard_liabilities', 30, function () {
            $currencies = Currency::all()->keyBy('symbol');

            // Exchange wallet balances per currency
            $exchangeWallets = Wallet::where('user_id', $this->exchangeUserId)
                ->get()
                ->keyBy('currency_symbol');

            // User wallet balances aggregated per currency
            $userBalances = Wallet::where('user_id', '!=', $this->exchangeUserId)
                ->selectRaw('currency_symbol, SUM(balance + locked_balance) as total_balance')
                ->groupBy('currency_symbol')
                ->pluck('total_balance', 'currency_symbol');

            $breakdown = [];
            $totalDebtUsdt = 0;

            foreach ($currencies as $symbol => $currency) {
                $userBalance = (float) ($userBalances[$symbol] ?? 0);
                $exchangeWallet = $exchangeWallets->get($symbol);
                $exchangeBalance = $exchangeWallet ? (float) ($exchangeWallet->balance + $exchangeWallet->locked_balance) : 0;

                if ($userBalance == 0 && $exchangeBalance == 0) {
                    continue;
                }

                $exchangePrice = $currency->exchange_price ?? 1;
                $difference = $exchangeBalance - $userBalance;
                $userBalanceUsdt = $userBalance * $exchangePrice;
                $exchangeBalanceUsdt = $exchangeBalance * $exchangePrice;
                $differenceUsdt = $difference * $exchangePrice;

                $totalDebtUsdt += $userBalanceUsdt;

                $breakdown[] = [
                    'symbol' => $symbol,
                    'name' => $currency->name ?? $symbol,
                    'userBalance' => formatNumberTrimZeros($userBalance, 8),
                    'userBalanceUsdt' => round($userBalanceUsdt, 2),
                    'exchangeBalance' => formatNumberTrimZeros($exchangeBalance, 8),
                    'exchangeBalanceUsdt' => round($exchangeBalanceUsdt, 2),
                    'difference' => formatNumberTrimZeros($difference, 8),
                    'differenceUsdt' => round($differenceUsdt, 2),
                    'isNegative' => $difference < 0,
                ];
            }

            // Sort by user balance USDT descending
            usort($breakdown, fn($a, $b) => $b['userBalanceUsdt'] <=> $a['userBalanceUsdt']);

            return [
                'totalDebtUsdt' => $totalDebtUsdt,
                'breakdown' => $breakdown,
            ];
        });

        return response()->json([
            'totalDebtUsdt' => formatNumberTrimZeros($data['totalDebtUsdt'], 2),
            'totalDebtUsdtRaw' => $data['totalDebtUsdt'],
            'breakdown' => $data['breakdown'],
        ]);
    }

    public function getCashFlowStats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $dateStart = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
        $dateEnd = $endDate ? Carbon::parse($endDate)->endOfDay() : now();

        $currencies = Currency::all()->keyBy('symbol');

        // Deposits grouped by currency (only confirmed)
        $deposits = Deposit::where('status', DepositStatusEnum::CONFIRMED)
            ->whereBetween('confirmed_at', [$dateStart, $dateEnd])
            ->selectRaw('currency_symbol, SUM(amount) as total_amount, SUM(usdt_value) as total_usdt, COUNT(*) as count')
            ->groupBy('currency_symbol')
            ->get()
            ->keyBy('currency_symbol');

        // Withdrawals grouped by currency (only completed)
        $withdrawals = Withdrawal::where('status', WithdrawalStatusEnum::COMPLETED)
            ->whereBetween('confirmed_at', [$dateStart, $dateEnd])
            ->selectRaw('currency_symbol, SUM(amount) as total_amount, SUM(usdt_value) as total_usdt, COUNT(*) as count')
            ->groupBy('currency_symbol')
            ->get()
            ->keyBy('currency_symbol');

        $allSymbols = $deposits->keys()->merge($withdrawals->keys())->unique();

        $totalDepositUsdt = 0;
        $totalWithdrawalUsdt = 0;
        $breakdown = [];

        foreach ($allSymbols as $symbol) {
            $currency = $currencies->get($symbol);
            $dep = $deposits->get($symbol);
            $wth = $withdrawals->get($symbol);

            $depAmount = $dep ? (float) $dep->total_amount : 0;
            $depUsdt = $dep ? (float) $dep->total_usdt : 0;
            $depCount = $dep ? (int) $dep->count : 0;

            $wthAmount = $wth ? (float) $wth->total_amount : 0;
            $wthUsdt = $wth ? (float) $wth->total_usdt : 0;
            $wthCount = $wth ? (int) $wth->count : 0;

            $netAmount = $depAmount - $wthAmount;
            $netUsdt = $depUsdt - $wthUsdt;

            $totalDepositUsdt += $depUsdt;
            $totalWithdrawalUsdt += $wthUsdt;

            $breakdown[] = [
                'symbol' => $symbol,
                'name' => $currency->name ?? $symbol,
                'depositAmount' => formatNumberTrimZeros($depAmount, 8),
                'depositUsdt' => round($depUsdt, 2),
                'depositCount' => $depCount,
                'withdrawalAmount' => formatNumberTrimZeros($wthAmount, 8),
                'withdrawalUsdt' => round($wthUsdt, 2),
                'withdrawalCount' => $wthCount,
                'netAmount' => formatNumberTrimZeros($netAmount, 8),
                'netUsdt' => round($netUsdt, 2),
                'isNetNegative' => $netUsdt < 0,
            ];
        }

        // Sort by deposit USDT descending
        usort($breakdown, fn($a, $b) => $b['depositUsdt'] <=> $a['depositUsdt']);

        $netCashFlowUsdt = $totalDepositUsdt - $totalWithdrawalUsdt;

        return response()->json([
            'totalDepositUsdt' => formatNumberTrimZeros($totalDepositUsdt, 2),
            'totalWithdrawalUsdt' => formatNumberTrimZeros($totalWithdrawalUsdt, 2),
            'netCashFlowUsdt' => formatNumberTrimZeros($netCashFlowUsdt, 2),
            'isNetNegative' => $netCashFlowUsdt < 0,
            'currencyCount' => count($breakdown),
            'breakdown' => $breakdown,
        ]);
    }

    public function getExpenseStats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $dateStart = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
        $dateEnd = $endDate ? Carbon::parse($endDate)->endOfDay() : now();

        // Fee subtype groupings
        $transferFeeSubtypes = [TransactionSubTypeEnum::EXCHANGE_WITHDRAWAL_FEE];
        $networkFeeSubtypes = [TransactionSubTypeEnum::NETWORK_WITHDRAWAL_FEE, TransactionSubTypeEnum::HD_WALLET_FEE];
        $refExchangeFeeSubtypes = [
            TransactionSubTypeEnum::REF_EXCHANGE_BUY_FEE,
            TransactionSubTypeEnum::REF_EXCHANGE_SELL_FEE,
            TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL_FEE,
        ];

        $allFeeSubtypes = array_merge(
            $transferFeeSubtypes,
            $networkFeeSubtypes,
            $refExchangeFeeSubtypes
        );

        // Query all fee transactions grouped by wallet's currency
        $feeTransactions = Transaction::where('transactions.type', TransactionTypeEnum::FEE)
            ->whereIn('transactions.subtype', $allFeeSubtypes)
            ->whereBetween('transactions.created_at', [$dateStart, $dateEnd])
            ->join('wallets', 'transactions.wallet_id', '=', 'wallets.id')
            ->selectRaw('wallets.currency_symbol, transactions.subtype, SUM(ABS(transactions.amount) * transactions.coin_price) as total_usdt, COUNT(*) as count')
            ->groupBy('wallets.currency_symbol', 'transactions.subtype')
            ->get();

        $currencies = Currency::all()->keyBy('symbol');

        // Build per-currency breakdown
        $currencyData = [];

        foreach ($feeTransactions as $row) {
            $symbol = $row->currency_symbol;
            if (!isset($currencyData[$symbol])) {
                $currency = $currencies->get($symbol);
                $currencyData[$symbol] = [
                    'symbol' => $symbol,
                    'name' => $currency->name ?? $symbol,
                    'transferFeesUsdt' => 0,
                    'networkFeesUsdt' => 0,
                    'refExchangeFeesUsdt' => 0,
                    'totalUsdt' => 0,
                ];
            }

            $usdt = (float) $row->total_usdt;
            $subtype = $row->subtype instanceof TransactionSubTypeEnum ? $row->subtype : TransactionSubTypeEnum::from($row->subtype);

            if (in_array($subtype, $transferFeeSubtypes)) {
                $currencyData[$symbol]['transferFeesUsdt'] += $usdt;
            } elseif (in_array($subtype, $networkFeeSubtypes)) {
                $currencyData[$symbol]['networkFeesUsdt'] += $usdt;
            } elseif (in_array($subtype, $refExchangeFeeSubtypes)) {
                $currencyData[$symbol]['refExchangeFeesUsdt'] += $usdt;
            }

            $currencyData[$symbol]['totalUsdt'] += $usdt;
        }

        $breakdown = array_values($currencyData);

        // Sort by total USDT descending
        usort($breakdown, fn($a, $b) => $b['totalUsdt'] <=> $a['totalUsdt']);

        // Calculate totals
        $totalTransferFees = array_sum(array_column($breakdown, 'transferFeesUsdt'));
        $totalNetworkFees = array_sum(array_column($breakdown, 'networkFeesUsdt'));
        $totalRefExchangeFees = array_sum(array_column($breakdown, 'refExchangeFeesUsdt'));
        $totalFees = $totalTransferFees + $totalNetworkFees + $totalRefExchangeFees;

        // Round breakdown values
        $breakdown = array_map(function ($item) {
            $item['transferFeesUsdt'] = round($item['transferFeesUsdt'], 2);
            $item['networkFeesUsdt'] = round($item['networkFeesUsdt'], 2);
            $item['refExchangeFeesUsdt'] = round($item['refExchangeFeesUsdt'], 2);
            $item['totalUsdt'] = round($item['totalUsdt'], 2);
            return $item;
        }, $breakdown);

        return response()->json([
            'totalFees' => formatNumberTrimZeros($totalFees, 2),
            'totalTransferFees' => formatNumberTrimZeros($totalTransferFees, 2),
            'totalNetworkFees' => formatNumberTrimZeros($totalNetworkFees, 2),
            'totalRefExchangeFees' => formatNumberTrimZeros($totalRefExchangeFees, 2),
            'totalTransferFeesRaw' => round($totalTransferFees, 2),
            'totalNetworkFeesRaw' => round($totalNetworkFees, 2),
            'totalRefExchangeFeesRaw' => round($totalRefExchangeFees, 2),
            'currencyCount' => count($breakdown),
            'breakdown' => $breakdown,
        ]);
    }

    public function getProfitLossStats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $dateStart = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
        $dateEnd = $endDate ? Carbon::parse($endDate)->endOfDay() : now();

        // ============ TRADE SPREAD (OTC Orders) ============
        $orders = OTCOrder::where('status', 'success')
            ->whereBetween('created_at', [$dateStart, $dateEnd])
            ->with(['market.activeExchangePrice', 'market.quoteCurrency', 'market.baseCurrency'])
            ->get();

        $currencies = Currency::all()->keyBy('symbol');
        $spreadBySymbol = [];

        foreach ($orders as $order) {
            $exchangePrice = $order->market?->activeExchangePrice;
            if (!$exchangePrice) continue;

            $baseCurrency = $order->market->base_currency;
            $quoteExchangePrice = $order->market?->quoteCurrency?->exchange_price ?? 1;
            $isBuy = $order->type === OTCOrderTypeEnum::BUY;

            $markup = $isBuy ? $exchangePrice->exchange_profit_sell : $exchangePrice->exchange_profit_buy;
            $denominator = 100 + $markup;

            if ($denominator == 0 || $markup == 0) {
                $spreadPerUnit = 0;
            } else {
                $spreadPerUnit = $isBuy
                    ? $order->price * $markup / $denominator
                    : $order->price * (-$markup) / $denominator;
            }

            $spreadUsdt = $spreadPerUnit * $order->quantity * $quoteExchangePrice;
            $volumeUsdt = $order->price * $order->quantity * $quoteExchangePrice;

            if (!isset($spreadBySymbol[$baseCurrency])) {
                $currency = $currencies->get($baseCurrency);
                $spreadBySymbol[$baseCurrency] = [
                    'symbol' => $baseCurrency,
                    'name' => $currency->name ?? $baseCurrency,
                    'tradeCount' => 0,
                    'buyCount' => 0,
                    'sellCount' => 0,
                    'spreadUsdt' => 0,
                    'volumeUsdt' => 0,
                ];
            }

            $spreadBySymbol[$baseCurrency]['tradeCount']++;
            $spreadBySymbol[$baseCurrency][$isBuy ? 'buyCount' : 'sellCount']++;
            $spreadBySymbol[$baseCurrency]['spreadUsdt'] += $spreadUsdt;
            $spreadBySymbol[$baseCurrency]['volumeUsdt'] += $volumeUsdt;
        }

        // Calculate avg spread % and round values
        $spreadBreakdown = array_values(array_map(function ($item) {
            $item['avgSpreadPct'] = $item['volumeUsdt'] > 0
                ? round($item['spreadUsdt'] / $item['volumeUsdt'] * 100, 2)
                : 0;
            $item['spreadUsdt'] = round($item['spreadUsdt'], 2);
            $item['volumeUsdt'] = round($item['volumeUsdt'], 2);
            return $item;
        }, $spreadBySymbol));

        usort($spreadBreakdown, fn($a, $b) => $b['spreadUsdt'] <=> $a['spreadUsdt']);

        $totalSpread = array_sum(array_column($spreadBreakdown, 'spreadUsdt'));

        // ============ COMMISSION REVENUE ============
        $commissionRevenue = (float) Transaction::where('type', TransactionTypeEnum::FEE)
            ->whereIn('subtype', [TransactionSubTypeEnum::OTC, TransactionSubTypeEnum::SPOT])
            ->whereBetween('created_at', [$dateStart, $dateEnd])
            ->selectRaw('SUM(ABS(amount) * coin_price) as total')
            ->value('total') ?? 0;

        // ============ EXPENSES ============
        $expenseSubtypes = [
            TransactionSubTypeEnum::EXCHANGE_WITHDRAWAL_FEE,
            TransactionSubTypeEnum::NETWORK_WITHDRAWAL_FEE,
            TransactionSubTypeEnum::HD_WALLET_FEE,
            TransactionSubTypeEnum::REF_EXCHANGE_BUY_FEE,
            TransactionSubTypeEnum::REF_EXCHANGE_SELL_FEE,
            TransactionSubTypeEnum::REF_EXCHANGE_WITHDRAWAL_FEE,
        ];

        $totalExpenses = (float) Transaction::where('type', TransactionTypeEnum::FEE)
            ->whereIn('subtype', $expenseSubtypes)
            ->whereBetween('created_at', [$dateStart, $dateEnd])
            ->selectRaw('SUM(ABS(amount) * coin_price) as total')
            ->value('total') ?? 0;

        // ============ P&L CALCULATIONS ============
        $totalRevenue = $totalSpread + $commissionRevenue;
        $totalProfitLoss = $totalRevenue - $totalExpenses;
        $netProfitMargin = $totalRevenue > 0 ? ($totalProfitLoss / $totalRevenue) * 100 : 0;

        return response()->json([
            'totalSpread' => formatNumberTrimZeros($totalSpread, 2),
            'totalSpreadRaw' => round($totalSpread, 2),
            'totalCommission' => formatNumberTrimZeros($commissionRevenue, 2),
            'totalExpenses' => formatNumberTrimZeros($totalExpenses, 2),
            'totalRevenue' => formatNumberTrimZeros($totalRevenue, 2),
            'totalProfitLoss' => formatNumberTrimZeros(abs($totalProfitLoss), 2),
            'totalProfitLossRaw' => round($totalProfitLoss, 2),
            'isProfitNegative' => $totalProfitLoss < 0,
            'netProfitMargin' => number_format(abs($netProfitMargin), 1),
            'isMarginNegative' => $netProfitMargin < 0,
            'currencyCount' => count($spreadBreakdown),
            'spreadBreakdown' => $spreadBreakdown,
        ]);
    }

    public function getStockPurchaseStats(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('direction', 'desc');
        $perPage = (int) $request->input('per_page', 15);

        $allowedSorts = ['created_at', 'amount', 'total_value', 'contract_number'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $query = StockContract::with(['user:id,first_name,last_name,email', 'stock:id,name,value,type']);

        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderBy($sortField, $sortDir);

        $paginated = $query->paginate($perPage);

        // Summary stats
        $summaryQuery = StockContract::query();
        if ($startDate) {
            $summaryQuery->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $summaryQuery->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $totalContracts = $summaryQuery->count();
        $totalValue = (float) $summaryQuery->sum('total_value');
        $totalAmount = (float) $summaryQuery->sum('amount');
        $activeContracts = (clone $summaryQuery)->where('contract_status', StockContractStatusEnum::ACTIVE)->count();

        $items = $paginated->map(function ($contract) {
            $userName = '';
            if ($contract->user) {
                $parts = array_filter([$contract->user->first_name, $contract->user->last_name]);
                $userName = implode(' ', $parts) ?: $contract->user->email;
            }

            return [
                'id' => $contract->id,
                'contractNumber' => $contract->contract_number,
                'userName' => $userName,
                'userEmail' => $contract->user?->email ?? '',
                'stockName' => $contract->stock?->name ?? '-',
                'stockType' => $contract->stock?->type?->value ?? '-',
                'amount' => formatNumberTrimZeros($contract->amount, 4),
                'totalValue' => formatNumberTrimZeros($contract->total_value, 2),
                'status' => $contract->contract_status?->value ?? '-',
                'statusLabel' => $contract->contract_status?->label() ?? '-',
                'statusColor' => $contract->contract_status?->color() ?? 'secondary',
                'date' => $contract->created_at?->format('Y/m/d H:i'),
            ];
        });

        return response()->json([
            'totalContracts' => number_format($totalContracts),
            'totalValue' => formatNumberTrimZeros($totalValue, 2),
            'totalAmount' => formatNumberTrimZeros($totalAmount, 4),
            'activeContracts' => number_format($activeContracts),
            'items' => $items,
            'pagination' => [
                'currentPage' => $paginated->currentPage(),
                'lastPage' => $paginated->lastPage(),
                'perPage' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function exportStockPurchases(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = StockContract::with(['user:id,first_name,last_name,email', 'stock:id,name,value,type'])
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $data = $query->get()->map(function ($contract) {
            $userName = '';
            if ($contract->user) {
                $parts = array_filter([$contract->user->first_name, $contract->user->last_name]);
                $userName = implode(' ', $parts) ?: $contract->user->email;
            }

            return [
                $contract->contract_number,
                $contract->created_at?->format('Y/m/d H:i'),
                $userName,
                $contract->user?->email ?? '',
                $contract->stock?->name ?? '-',
                formatNumberTrimZeros($contract->amount, 4),
                formatNumberTrimZeros($contract->total_value, 2),
                $contract->contract_status?->label() ?? '-',
            ];
        });

        return Excel::download(new StockPurchaseExport($data), 'stock-purchases-' . now()->format('Y-m-d') . '.xlsx');
    }
}
