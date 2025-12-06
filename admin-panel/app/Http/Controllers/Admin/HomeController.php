<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\ExchangeTransaction;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\User;
use App\Models\OTCOrder;
use App\Models\SpotTrade;
use App\Models\SpotOrder;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;


class HomeController extends Controller
{
    protected $bitexroomUserId;

    public function __construct()
    {
        $this->bitexroomUserId = config('bitexroom.user_id', 1);
    }

    public function index()
    {
        $admin = auth()->guard('admin')->user();
        if (empty($admin->two_factor_secret) && app()->environment() == 'production') {
            $is2FAEnabled = false;
        } else {
            $is2FAEnabled = true;
        }

        return view('dashboard.home.index', [
            'is2FAEnabled' => $is2FAEnabled
        ]);
    }

    // AJAX Endpoints

    public function getKPIStats()
    {
        $totalUsers = User::count();
        $activeUsers = User::online()->count();
        $todayRegistrations = User::whereDate('created_at', today())->count();

        // حجم معاملات هفته گذشته (Spot + OTC)
        $weeklySpotVolume = SpotTrade::whereBetween('created_at', [now()->subDays(7), now()])
            ->with('market')
            ->get()
            ->sum(function ($trade) {
                return $trade->quantity * $trade->price * ($trade->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $weeklyOTCVolume = OTCOrder::whereBetween('created_at', [now()->subDays(7), now()])
            ->where('status', 'success')
            ->with('market')
            ->get()
            ->sum(function ($order) {
                return $order->quantity * $order->price * ($order->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $weeklyTradingVolume = $weeklySpotVolume + $weeklyOTCVolume;

        $todaySpotVolume = SpotTrade::whereDate('created_at', today())
            ->with('market')
            ->get()
            ->sum(function ($trade) {
                return $trade->quantity * $trade->price * ($trade->market?->quoteCurrency?->exchange_price ?? 1);
            });

        $todayOTCVolume = OTCOrder::whereDate('created_at', today())
            ->where('status', 'success')
            ->with('market')
            ->get()
            ->sum(function ($order) {
                return $order->quantity * $order->price;
            });

        $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();

        return response()->json([
            'totalUsers' => number_format($totalUsers),
            'activeUsers' => number_format($activeUsers),
            'todayRegistrations' => number_format($todayRegistrations),
            'weeklyTradingVolume' => formatNumberTrimZeros($weeklyTradingVolume),
            'todaySpotVolume' =>  formatNumberTrimZeros($todaySpotVolume, 2),
            'todayOTCVolume' => formatNumberTrimZeros($todayOTCVolume, 2),
            'pendingWithdrawals' => number_format($pendingWithdrawals),
        ]);
    }

    public function getFinancialSummary(Request $request)
    {
        $type = $request->input('type', 'withdrawals');

        switch ($type) {
            case 'withdrawals':
                $data = Withdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount')
                    ->where('status', WithdrawalStatusEnum::COMPLETED)
                    ->where('user_id', '!=', $this->bitexroomUserId)
                    ->groupBy('currency_symbol')
                    ->get()
                    ->map(function ($item) {
                        $currency = \App\Models\Currency::where('symbol', $item->currency_symbol)->first();
                        $item->logo = $currency ? $currency->coinLogo() : asset('images/coins/default.png');
                        return $item;
                    });
                break;

            case 'otc_fees':
                $data = Transaction::where('type', TransactionTypeEnum::FEE)
                    ->where('subtype', TransactionSubTypeEnum::OTC)
                    ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
                    ->selectRaw('wallets.currency_symbol, SUM(transactions.amount) as total_amount')
                    ->groupBy('wallets.currency_symbol')
                    ->get()
                    ->map(function ($item) {
                        $currency = \App\Models\Currency::where('symbol', $item->currency_symbol)->first();
                        $item->logo = $currency ? $currency->coinLogo() : asset('images/coins/default.png');
                        return $item;
                    });
                break;

            case 'withdrawal_fees':
                $data = Transaction::where('type', TransactionTypeEnum::FEE)
                    ->where('subtype', TransactionSubTypeEnum::EXCHANGE_WITHDRAWAL_FEE)
                    ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
                    ->selectRaw('wallets.currency_symbol, SUM(transactions.amount) as total_amount')
                    ->groupBy('wallets.currency_symbol')
                    ->get()
                    ->map(function ($item) {
                        $currency = \App\Models\Currency::where('symbol', $item->currency_symbol)->first();
                        $item->logo = $currency ? $currency->coinLogo() : asset('images/coins/default.png');
                        return $item;
                    });
                break;

            case 'ref_purchases':
                $data = ExchangeTransaction::with('currency')
                    ->selectRaw('currency_symbol, SUM(amount) as total_amount')
                    ->groupBy('currency_symbol')
                    ->get()
                    ->map(function ($transaction) {
                        $transaction->total_filled_value = ExchangeTransaction::where('currency_symbol', $transaction->currency_symbol)
                            ->get()
                            ->sum(function ($t) {
                                return (float)data_get($t, 'response.data.filled_value', 0);
                            });
                        $transaction->logo = $transaction->currency ? $transaction->currency->coinLogo() : asset('images/coins/default.png');
                        return $transaction;
                    });
                break;

            case 'ref_withdrawals':
                $data = ExchangeAssetsWithdrawal::selectRaw('currency_symbol, SUM(amount) as total_amount')
                    ->groupBy('currency_symbol')
                    ->get()
                    ->map(function ($item) {
                        $currency = \App\Models\Currency::where('symbol', $item->currency_symbol)->first();
                        $item->logo = $currency ? $currency->coinLogo() : asset('images/coins/default.png');
                        return $item;
                    });
                break;

            default:
                $data = collect();
        }

        return response()->json(['data' => $data]);
    }

    public function getDepositWithdrawCharts()
    {
        // آماده کردن 7 روز گذشته
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $last7Days->push([
                'date' => $date->format('Y-m-d'),
                'date_label' => jdate($date->format('Y-m-d'))->format('d M'), // فرمت فارسی
                'gregorian_date' => $date->format('M d, Y'), // فرمت میلادی
                'count' => 0,
                'value' => 0
            ]);
        }

        // دریافت واریزهای 7 روز گذشته
        $depositsData = Deposit::with('currency')
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->get()
            ->groupBy(function ($deposit) {
                return $deposit->created_at->format('Y-m-d');
            });

        // پر کردن داده‌ها
        $deposits = $last7Days->map(function ($day) use ($depositsData) {
            if ($depositsData->has($day['date'])) {
                $dayDeposits = $depositsData[$day['date']];
                $day['count'] = $dayDeposits->count();
                $day['value'] = $dayDeposits->sum(function ($deposit) {
                    return $deposit->amount * ($deposit->currency?->exchange_price ?? 0);
                });
            }
            return $day;
        });

        // دریافت برداشت‌های 7 روز گذشته
        $withdrawalsData = Withdrawal::with('currency')
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->get()
            ->groupBy(function ($withdrawal) {
                return $withdrawal->created_at->format('Y-m-d');
            });

        // پر کردن داده‌ها برای برداشت‌ها (استفاده از همان structure برای consistency)
        $withdrawals = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayData = [
                'date' => $date->format('Y-m-d'),
                'date_label' => jdate($date->format('Y-m-d'))->format('d M'),
                'gregorian_date' => $date->format('M d, Y'),
                'count' => 0,
                'value' => 0
            ];

            if ($withdrawalsData->has($dayData['date'])) {
                $dayWithdrawals = $withdrawalsData[$dayData['date']];
                $dayData['count'] = $dayWithdrawals->count();
                $dayData['value'] = $dayWithdrawals->sum(function ($withdrawal) {
                    return $withdrawal->amount * ($withdrawal->currency?->exchange_price ?? 0);
                });
            }

            $withdrawals->push($dayData);
        }

        // محاسبه مجموع کل
        $totalDepositsValue = $deposits->sum('value');
        $totalWithdrawalValue = $withdrawals->sum('value');

        return response()->json([
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'totalDepositsValue' => formatNumberTrimZeros($totalDepositsValue),
            'totalWithdrawalValue' => formatNumberTrimZeros($totalWithdrawalValue),
        ]);
    }

    public function getTradingStats()
    {
        $otcBuyLastWeek = OTCOrder::where('type', 'buy')
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek()])
            ->count();

        $otcSellLastWeek = OTCOrder::where('type', 'sell')
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek()])
            ->count();

        $spotTradesLastWeek = SpotTrade::whereBetween('created_at', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek()])
            ->count();

        return response()->json([
            'otcBuyLastWeek' => $otcBuyLastWeek,
            'otcSellLastWeek' => $otcSellLastWeek,
            'spotTradesLastWeek' => $spotTradesLastWeek,
        ]);
    }

    public function getRecentActivities()
    {
        $recentDeposits = Deposit::with('user', 'currency')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($deposit) {
                return [
                    'type' => 'deposit',
                    'user' => $deposit->user?->username ?? 'N/A',
                    'amount' => formatNumberTrimZeros($deposit->amount),
                    'currency' => $deposit->currency_symbol,
                    'time' => $deposit->created_at->diffForHumans(),
                    'status' => $deposit->status->value,
                ];
            });

        $recentWithdrawals = Withdrawal::with('user', 'currency')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($withdrawal) {
                return [
                    'type' => 'withdrawal',
                    'user' => $withdrawal->user?->username ?? 'N/A',
                    'amount' => formatNumberTrimZeros($withdrawal->amount),
                    'currency' => $withdrawal->currency_symbol,
                    'time' => $withdrawal->created_at->diffForHumans(),
                    'status' => $withdrawal->status->value,
                ];
            });

        $activities = $recentDeposits->merge($recentWithdrawals)->sortByDesc('time')->take(10)->values();

        return response()->json(['activities' => $activities]);
    }

    public function getTopTradingPairs()
    {
        // Top Spot Trading Pairs
        $topSpotPairs = SpotTrade::with(['market.baseCurrency', 'market.quoteCurrency'])
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->selectRaw('market_id, COUNT(*) as trades_count, SUM(quantity * price) as volume')
            ->groupBy('market_id')
            ->orderBy('volume', 'desc')
            ->take(5)
            ->get()
            ->map(function ($trade) {
                if (!$trade->market) {
                    return null;
                }

                return [
                    'pair' => $trade->market->base_currency . '/' . $trade->market->quote_currency,
                    'trades' => number_format($trade->trades_count),
                    'volume' => formatNumberTrimZeros($trade->volume),
                ];
            })
            ->filter()
            ->values();

        // Top OTC Trading Pairs - باید global scope رو حذف کنیم
        $topOTCPairs = OTCOrder::withoutGlobalScope('withTotalValue')
            ->with(['market.baseCurrency', 'market.quoteCurrency'])
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->whereNotNull('market_id')
            ->selectRaw('market_id, COUNT(*) as trades_count, SUM(quantity * price) as volume')
            ->groupBy('market_id')
            ->orderBy('volume', 'desc')
            ->take(5)
            ->get()
            ->map(function ($order) {
                if (!$order->market) {
                    return null;
                }

                return [
                    'pair' => $order->market->base_currency . '/' . $order->market->quote_currency,
                    'trades' => number_format($order->trades_count),
                    'volume' => formatNumberTrimZeros($order->volume),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'spotPairs' => $topSpotPairs,
            'otcPairs' => $topOTCPairs
        ]);
    }
}
