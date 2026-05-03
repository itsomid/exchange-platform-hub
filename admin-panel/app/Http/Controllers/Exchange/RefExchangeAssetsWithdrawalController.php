<?php

namespace App\Http\Controllers\Exchange;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Exceptions\Exchange\CoinexWithdrawalException;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\OTCRefExchangeWithdrawal;
use App\Models\Exchange;
use App\Models\Setting;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\DTO\ChargeCurrencyRequestDTO;
use App\Repositories\ExchangeRepository;
use App\Services\Exchanges\ExchangeService;
use App\Services\Wallet\WalletService;
use App\Http\Requests\Exchange\RefExchangeAssetsWithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RefExchangeAssetsWithdrawalController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly ExchangeRepository $exchangeRepository,
    ) {}
    public function index()
    {
        $withdraws = ExchangeAssetsWithdrawal::with('currency')->orderBy('id', 'desc')->get();

        $withdrawalFeeSum = ExchangeAssetsWithdrawal::sum('fee');

        return view('dashboard.exchange.ref_exchange.assets-withdrawal-history', [
            'withdraws' => $withdraws,
            'withdrawalFeeSum' => $withdrawalFeeSum,
        ]);
    }


    public function create(Request $request)
    {

        if ($request->has('currency_symbol')) {
            $currency_symbol = $request->currency_symbol;
        } else {
            $currency_symbol = 'USDT';
        }
        if ($request->has('exchange')) {
            $exchange = Exchange::where('slug', $request->exchange)->first();
            $exchangeName = $exchange->name;
        } else {
            $exchangeName = 'coinex';
        }
        $currency = Currency::whereSymbol($currency_symbol)->first();

        $currencyChains = $currency->chains;


        $wallet = $this->walletService->getExchangeWallet($currency->symbol);

        $walletChains = $wallet?->walletChains;

        // Get balance from exchange using AssetFactory
        $exchangeBalance = null;
        try {
            $exchangeSlug = $request->exchange ?? 'coinex';
            $assetService = AssetFactory::make($exchangeSlug);
            $balances = $assetService->getBalance();

            // Find balance for the specific currency
            foreach ($balances as $balance) {
                if ($balance->getCcy() === $currency->symbol) {
                    $exchangeBalance = $balance;
                    break;
                }
            }
        } catch (\Throwable $e) {
            // Log error but continue - balance will be null
            report($e);
        }

        // Get all exchanges for selection
        $exchanges = Exchange::all();
        $selectedExchange = $request->exchange ?? 'coinex';

        return view('dashboard.exchange.ref_exchange.assets-withdrawal-request-form', [
            'currency' => $currency,
            'currencyChains' => $currencyChains,
            'walletChains' => $walletChains,
            'exchangeName' => $exchangeName,
            'exchangeBalance' => $exchangeBalance,
            'exchanges' => $exchanges,
            'selectedExchange' => $selectedExchange,
        ]);
    }

    public function store(RefExchangeAssetsWithdrawalRequest $request)
    {

        $currency = Currency::where('symbol', $request->input('currency_symbol'))->first();

        if (!$currency) {
            return redirect()->back()->withErrors(['currency' => 'ارز انتخاب شده معتبر نیست.']);
        }

        // Retrieve valid chains for this currency
        $validChains = $currency->chains()->pluck('chain')->map(fn($chain) => $chain->value)->toArray();
        // Check if the selected chain is valid
        if (!in_array($request->input('currency_chain'), $validChains)) {
            return redirect()->back()->withErrors(['chain' => 'شبکه انتخاب شده با ارز مطابقت ندارد.']);
        }
        try {

            $exchangeService = resolve(ExchangeService::class);
            $selectedExchange = $this->exchangeRepository->getExchangeBySlug($request->input('exchange_slug'));

            if (!$selectedExchange) {
                return redirect()->back()->withErrors(['exchange_slug' => 'صرافی انتخاب شده معتبر نیست.']);
            }

            $exchangeService->chargeCurrency(
                resolve(ChargeCurrencyRequestDTO::class)
                    ->setCurrency($currency->symbol)
                    ->setCurrencyChain($request->input('currency_chain'))
                    ->setQuantity($request->input('amount'))
                    ->setExchangeSlug($selectedExchange->slug)
            );

            Toast::message('درخواست برداشت ثبت شد و تا دقایقی دیگر منتقل می گردد.')
                ->success()
                ->notify();

            return redirect()->route('admin.ref-exchange.assets-gathering-to-hd-wallet.index');
        } catch (CantResolveCoinexException $e) {
            report($e);
            Toast::message('عملیات با شکست مواجه شد.')
                ->danger()
                ->notify();
            return redirect()->back()->withInput()->with('operation_errors', [
                trim($e->getMessage()) !== '' ? $e->getMessage() : 'خطا در برقراری ارتباط با Coinex',
            ]);
        } catch (CoinexWithdrawalException $e) {
            report($e);
            Toast::message('عملیات با شکست مواجه شد.')
                ->danger()
                ->notify();
            return redirect()->back()->withInput()->with('operation_errors', [
                trim($e->getMessage()) !== '' ? $e->getMessage() : 'خطا در برداشت از صرافی',
            ]);
        } catch (\Throwable $e) {
            report($e);
            Toast::message('عملیات با شکست مواجه شد.')
                ->danger()
                ->notify();
            return redirect()->back()->withInput()->with('operation_errors', [
                trim($e->getMessage()) !== '' ? $e->getMessage() : 'خطای ناشناخته در فرآیند برداشت',
            ]);
        }
    }

    public function getPendingRefExchangeWithdrawal()
    {
        $withdrawals = OTCRefExchangeWithdrawal::with(['currency', 'transaction'])
            ->orderByRaw("status = ? DESC", [OTCRefExchangeWithdrawalStatusEnum::PENDING->value])
            ->get();



        $pendingWithdrawals = OTCRefExchangeWithdrawal::select(
            'otc_ref_exchange_withdrawals.currency_id',
            DB::raw('SUM(transactions.amount) as total_withdraw_amount')
        )
            ->join('transactions', 'transactions.id', '=', 'otc_ref_exchange_withdrawals.transaction_id')
            ->where('otc_ref_exchange_withdrawals.status', OTCRefExchangeWithdrawalStatusEnum::PENDING->value)
            ->groupBy('otc_ref_exchange_withdrawals.currency_id')
            ->with('currency') // To get currency details
            ->get();

        // Get all exchanges for aggregation form
        $exchanges = Exchange::all();

        // Get all currencies for withdrawal settings
        $allCurrencies = Currency::orderBy('symbol')->get();

        // Get global withdrawal settings
        $globalWithdrawalInterval = (int) Setting::getSetting('exchange_withdrawal_period_time', 60);
        $globalWithdrawalMinCount = (int) Setting::getSetting('exchange_withdrawal_period_buy', 1);
        $withdrawalType = Setting::getSetting('exchange_withdrawal_type', 'exchange_withdrawal_period_time');

        // Calculate timer data for each pending withdrawal currency
        $currencyTimerData = [];
        foreach ($pendingWithdrawals as $pending) {
            $currency = $pending->currency;
            $cacheKey = 'exchange_withdrawal_period_time_last_hit_' . $currency->id;
            $lastHit = Cache::get($cacheKey);
            
            $intervalMinutes = $currency->ref_exchange_withdrawal_interval_minutes ?? $globalWithdrawalInterval;
            $minCount = $currency->ref_exchange_withdrawal_min_count ?? $globalWithdrawalMinCount;
            
            // Count pending transactions for this currency
            $pendingCount = OTCRefExchangeWithdrawal::where('currency_id', $currency->id)
                ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
                ->count();
            
            $remainingSeconds = 0;
            $isReady = false;
            $readyReason = null;
            
            if (!$currency->ref_exchange_withdrawal_enabled) {
                // Withdrawal disabled
                $remainingSeconds = -1; // Special value for disabled
            } elseif ($lastHit) {
                $minutesPassed = now()->diffInMinutes($lastHit, true);
                $remainingMinutes = max(0, $intervalMinutes - $minutesPassed);
                $remainingSeconds = $remainingMinutes * 60;
                
                // Check if ready based on time
                if ($remainingMinutes <= 0) {
                    $isReady = true;
                    $readyReason = 'time';
                }
            } else {
                // No cache = waiting for schedule:run to initialize timer
                $remainingSeconds = $intervalMinutes * 60;
                $isReady = false;
                $readyReason = null;
            }
            
            // Check count condition
            if ($pendingCount >= $minCount && !$isReady) {
                $isReady = true;
                $readyReason = 'count';
            }
            
            $currencyTimerData[$currency->id] = [
                'remaining_seconds' => $remainingSeconds,
                'interval_minutes' => $intervalMinutes,
                'min_count' => $minCount,
                'pending_count' => $pendingCount,
                'is_ready' => $isReady,
                'ready_reason' => $readyReason,
                'last_hit' => $lastHit ? $lastHit->toDateTimeString() : null,
                'enabled' => $currency->ref_exchange_withdrawal_enabled,
            ];
        }

        return view('dashboard.exchange.ref_exchange.pending-assets-withdrawal-history', [
            'withdrawals' => $withdrawals,
            'pendingWithdrawals' => $pendingWithdrawals,
            'exchanges' => $exchanges,
            'allCurrencies' => $allCurrencies,
            'globalWithdrawalInterval' => $globalWithdrawalInterval,
            'globalWithdrawalMinCount' => $globalWithdrawalMinCount,
            'withdrawalType' => $withdrawalType,
            'currencyTimerData' => $currencyTimerData,
        ]);
    }

    /**
     * Bulk aggregation of multiple currencies
     */
    public function bulkAggregate(Request $request)
    {
        $request->validate([
            'exchange_slug' => 'required|string|exists:exchanges,slug',
            'currencies' => 'required|array|min:1',
            'currencies.*.selected' => 'sometimes|in:1',
            'currencies.*.currency_id' => 'required|exists:currencies,id',
            'currencies.*.symbol' => 'required|string',
            'currencies.*.type' => 'required|in:amount,percent',
            'currencies.*.value' => 'nullable|numeric|min:0',
        ]);

        $selectedExchange = $this->exchangeRepository->getExchangeBySlug($request->input('exchange_slug'));

        if (!$selectedExchange) {
            Toast::message('صرافی انتخاب شده معتبر نیست.')
                ->danger()
                ->notify();
            return redirect()->back();
        }

        $currencies = collect($request->input('currencies'))
            ->filter(fn($currency) => isset($currency['selected']) && $currency['selected'] == '1');

        if ($currencies->isEmpty()) {
            Toast::message('لطفاً حداقل یک ارز را انتخاب کنید.')
                ->warning()
                ->notify();
            return redirect()->back();
        }

        $exchangeService = resolve(ExchangeService::class);
        $successCount = 0;
        $failedCurrencies = [];

        foreach ($currencies as $currencyData) {
            try {
                $currency = Currency::find($currencyData['currency_id']);
                
                if (!$currency) {
                    $failedCurrencies[] = $currencyData['symbol'] . ' (ارز یافت نشد)';
                    continue;
                }

                // Calculate actual amount based on type
                $pendingAmount = OTCRefExchangeWithdrawal::select(DB::raw('SUM(transactions.amount) as total'))
                    ->join('transactions', 'transactions.id', '=', 'otc_ref_exchange_withdrawals.transaction_id')
                    ->where('otc_ref_exchange_withdrawals.status', OTCRefExchangeWithdrawalStatusEnum::PENDING->value)
                    ->where('otc_ref_exchange_withdrawals.currency_id', $currency->id)
                    ->value('total') ?? 0;

                $amount = $currencyData['type'] === 'percent'
                    ? ($pendingAmount * floatval($currencyData['value'])) / 100
                    : floatval($currencyData['value']);

                if ($amount <= 0) {
                    $failedCurrencies[] = $currencyData['symbol'] . ' (مقدار نامعتبر)';
                    continue;
                }

                // Ensure amount doesn't exceed pending amount
                $amount = min($amount, $pendingAmount);

                // Get default chain for currency (first available chain)
                $currencyChain = $currency->chains()->first();
                
                if (!$currencyChain) {
                    $failedCurrencies[] = $currencyData['symbol'] . ' (شبکه یافت نشد)';
                    continue;
                }

                $exchangeService->chargeCurrency(
                    resolve(ChargeCurrencyRequestDTO::class)
                        ->setCurrency($currency->symbol)
                        ->setCurrencyChain($currencyChain->chain->value)
                        ->setQuantity($amount)
                        ->setExchangeSlug($selectedExchange->slug)
                );

                $successCount++;

            } catch (CantResolveCoinexException $e) {
                report($e);
                $failedCurrencies[] = $currencyData['symbol'] . ': ' . (trim($e->getMessage()) !== ''
                    ? $e->getMessage()
                    : 'خطا در برقراری ارتباط با Coinex');
            } catch (\Throwable $e) {
                report($e);
                $failedCurrencies[] = $currencyData['symbol'] . ': ' . (trim($e->getMessage()) !== ''
                    ? $e->getMessage()
                    : class_basename($e));
            }
        }

        // Generate appropriate message
        if ($successCount > 0 && empty($failedCurrencies)) {
            Toast::message("عملیات تجمیع برای {$successCount} ارز با موفقیت آغاز شد.")
                ->success()
                ->notify();
            return redirect()->route('admin.ref-exchange.assets-gathering-to-hd-wallet.pending-withdrawal');
        }

        Toast::message('عملیات با شکست مواجه شد.')
                ->danger()
                ->notify();

        return redirect()->route('admin.ref-exchange.assets-gathering-to-hd-wallet.pending-withdrawal')
            ->with('operation_errors', $failedCurrencies)
            ->with('operation_success_count', $successCount);
    }

    /**
     * Update withdrawal settings for a currency
     */
    public function updateCurrencyWithdrawalSettings(Request $request, int $currencyId)
    {
        $request->validate([
            'ref_exchange_withdrawal_enabled' => 'required|boolean',
            'ref_exchange_withdrawal_interval_minutes' => 'required|integer|min:1',
            'ref_exchange_withdrawal_min_count' => 'required|integer|min:1',
        ]);

        $currency = Currency::findOrFail($currencyId);

        $currency->update([
            'ref_exchange_withdrawal_enabled' => $request->boolean('ref_exchange_withdrawal_enabled'),
            'ref_exchange_withdrawal_interval_minutes' => $request->input('ref_exchange_withdrawal_interval_minutes'),
            'ref_exchange_withdrawal_min_count' => $request->input('ref_exchange_withdrawal_min_count'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "تنظیمات برداشت {$currency->symbol} با موفقیت بروزرسانی شد.",
            'currency' => $currency,
        ]);
    }

    /**
     * Bulk update withdrawal settings for multiple currencies
     */
    public function bulkUpdateCurrencyWithdrawalSettings(Request $request)
    {
        $request->validate([
            'currencies' => 'required|array|min:1',
            'currencies.*.id' => 'required|exists:currencies,id',
            'currencies.*.ref_exchange_withdrawal_enabled' => 'nullable|in:0,1',
            'currencies.*.ref_exchange_withdrawal_interval_minutes' => 'nullable|integer|min:1',
            'currencies.*.ref_exchange_withdrawal_min_count' => 'nullable|integer|min:1',
            'currencies.*.ref_exchange_withdrawal_aggregation_percent' => 'nullable|integer|min:1|max:100',
        ]);

        $updatedCount = 0;
        
        foreach ($request->input('currencies') as $currencyData) {
            $currency = Currency::find($currencyData['id']);
            if ($currency) {
                // Store old interval to detect changes
                $oldInterval = $currency->ref_exchange_withdrawal_interval_minutes;
                
                $currency->update([
                    'ref_exchange_withdrawal_enabled' => isset($currencyData['ref_exchange_withdrawal_enabled']) && $currencyData['ref_exchange_withdrawal_enabled'] == '1',
                    'ref_exchange_withdrawal_interval_minutes' => !empty($currencyData['ref_exchange_withdrawal_interval_minutes']) ? $currencyData['ref_exchange_withdrawal_interval_minutes'] : null,
                    'ref_exchange_withdrawal_min_count' => !empty($currencyData['ref_exchange_withdrawal_min_count']) ? $currencyData['ref_exchange_withdrawal_min_count'] : null,
                    'ref_exchange_withdrawal_aggregation_percent' => !empty($currencyData['ref_exchange_withdrawal_aggregation_percent']) ? $currencyData['ref_exchange_withdrawal_aggregation_percent'] : null,
                ]);
                
                // If interval changed, reset the cache timer to restart from now
                $newInterval = !empty($currencyData['ref_exchange_withdrawal_interval_minutes']) ? $currencyData['ref_exchange_withdrawal_interval_minutes'] : null;
                if ($oldInterval !== $newInterval) {
                    $cacheKey = 'exchange_withdrawal_period_time_last_hit_' . $currency->id;
                    Cache::forever($cacheKey, now());
                }
                
                $updatedCount++;
            }
        }

        Toast::message("تنظیمات برداشت {$updatedCount} ارز با موفقیت بروزرسانی شد.")
            ->success()
            ->notify();

        return redirect()->back();
    }

    /**
     * Toggle withdrawal status for a currency (AJAX)
     */
    public function toggleCurrencyWithdrawalStatus(int $currencyId)
    {
        $currency = Currency::findOrFail($currencyId);

        $currency->update([
            'ref_exchange_withdrawal_enabled' => !$currency->ref_exchange_withdrawal_enabled,
        ]);

        return response()->json([
            'success' => true,
            'enabled' => $currency->ref_exchange_withdrawal_enabled,
            'message' => $currency->ref_exchange_withdrawal_enabled 
                ? "برداشت {$currency->symbol} فعال شد." 
                : "برداشت {$currency->symbol} غیرفعال شد.",
        ]);
    }
}
