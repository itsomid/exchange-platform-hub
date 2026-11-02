<?php

namespace App\Http\Controllers\Setting;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\SpotOrderSourceEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSpotBotSettingRequest;
use App\Models\Currency;
use App\Models\SpotBotSetting;
use App\Models\User;
use App\Services\Bot\SpotBotOrderApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpotBotSettingController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->query('sort') === 'popular' ? 'popular' : 'symbol';

        $totalCurrencies = Currency::count();
        $activeBots = SpotBotSetting::where('is_active', true)->count();
        $configuredBots = SpotBotSetting::count();
        $inactiveBots = $totalCurrencies - $activeBots;

        $currencies = $this->orderCurrenciesByPreference(
            Currency::with(['spotBotSetting.fakeUser']),
            $sort
        );

        if ($sort === 'popular') {
            $currencies->selectRaw('COALESCE(user_trades.trades_count, 0) as user_trades_count');
        }

        $currencies = $currencies->paginate(20)->withQueryString();

        $activationCurrencies = $this->orderCurrenciesByPreference(
            Currency::with(['spotBotSetting:id,currency_id,is_active']),
            $sort
        )->get();

        return view('dashboard.setting.spot-bot.index', compact(
            'currencies',
            'totalCurrencies',
            'activeBots',
            'inactiveBots',
            'configuredBots',
            'sort',
            'activationCurrencies',
        ));
    }

    private function orderCurrenciesByPreference($query, string $sort)
    {
        if ($sort === 'popular') {
            return $query->leftJoinSub($this->userTradeCounts(), 'user_trades', function ($join) {
                $join->on('user_trades.symbol', '=', 'currencies.symbol');
            })
                ->select('currencies.*')
                ->orderByRaw('COALESCE(user_trades.trades_count, 0) DESC')
                ->orderBy('currencies.symbol');
        }

        return $query->orderBy('currencies.symbol');
    }

    private function userTradeCounts()
    {
        $otc = DB::table('otc_orders')
            ->join('markets', 'markets.id', '=', 'otc_orders.market_id')
            ->where('otc_orders.status', OTCOrderStatusEnum::SUCCESS->value)
            ->whereNull('otc_orders.deleted_at')
            ->selectRaw('markets.base_currency as symbol, COUNT(*) as trades_count')
            ->groupBy('markets.base_currency');

        $spot = DB::table('spot_orders')
            ->join('markets', 'markets.id', '=', 'spot_orders.market_id')
            ->whereIn('spot_orders.source', [SpotOrderSourceEnum::USER->value, SpotOrderSourceEnum::API->value])
            ->where('spot_orders.filled_quantity', '>', 0)
            ->selectRaw('markets.base_currency as symbol, COUNT(*) as trades_count')
            ->groupBy('markets.base_currency');

        return DB::query()
            ->fromSub($otc->unionAll($spot), 'combined')
            ->selectRaw('symbol, SUM(trades_count) as trades_count')
            ->groupBy('symbol');
    }

    public function bulkUpdateInterval(Request $request)
    {
        $data = $request->validate([
            'price_interval_seconds' => 'required|integer|min:1',
        ]);

        $updated = SpotBotSetting::query()->update([
            'price_interval_seconds' => $data['price_interval_seconds'],
        ]);

        if ($updated === 0) {
            Toast::message('هیچ بازار پیکربندی‌شده‌ای برای تغییر بازه زمانی وجود ندارد.')->warning()->notify();
        } else {
            Toast::message('بازه زمانی ' . $updated . ' بازار به ' . $data['price_interval_seconds'] . ' ثانیه تغییر کرد.')->success()->notify();
        }

        return back();
    }

    public function bulkDisable()
    {
        $updated = SpotBotSetting::query()->where('is_active', true)->update([
            'is_active' => false,
        ]);

        if ($updated === 0) {
            Toast::message('بازار فعالی برای غیرفعال کردن وجود ندارد.')->warning()->notify();
        } else {
            Toast::message($updated . ' بازار غیرفعال شد.')->success()->notify();
        }

        return back();
    }

    public function bulkActivate(Request $request)
    {
        $data = $request->validate([
            'currency_ids' => 'required|array|min:1',
            'currency_ids.*' => 'integer|exists:currencies,id',
        ], [
            'currency_ids.required' => 'حداقل یک کوین را انتخاب کنید.',
            'currency_ids.min' => 'حداقل یک کوین را انتخاب کنید.',
        ]);

        $activated = 0;

        foreach (Currency::whereIn('id', $data['currency_ids'])->get() as $currency) {
            $setting = SpotBotSetting::firstOrNew(['currency_id' => $currency->id]);

            if ($setting->exists && $setting->is_active) {
                continue;
            }

            if (! $setting->exists) {
                $setting->fill([
                    'price_interval_seconds' => 60,
                    'order_margin' => 0.5,
                    'buy_orders_count' => 5,
                    'sell_orders_count' => 5,
                    'market_crash_percentage' => 10,
                    'min_order_size' => 10,
                    'max_order_size' => 1000,
                ]);
            }

            $setting->is_active = true;
            $setting->save();
            $activated++;
        }

        if ($activated === 0) {
            Toast::message('کوین جدیدی برای فعال‌سازی انتخاب نشده بود.')->warning()->notify();
        } else {
            Toast::message($activated . ' کوین فعال شد.')->success()->notify();
        }

        return back();
    }

    public function cancelOrders(Currency $currency, SpotBotOrderApiClient $client)
    {
        $result = $client->cancelForCurrency($currency->id);
        $message = $result['ok']
            ? 'سفارش‌های باز ربات '.$currency->symbol.' بسته شد. تعداد: '.$result['cancelled']
            : $result['message'];

        if (request()->expectsJson()) {
            return response()->json([
                'success' => $result['ok'],
                'message' => $message,
            ], $result['ok'] ? 200 : 422);
        }

        $toast = Toast::message($message);
        $result['ok'] ? $toast->success() : $toast->danger();
        $toast->notify();

        return back();
    }

    public function cancelInactiveOrders(SpotBotOrderApiClient $client)
    {
        $settings = SpotBotSetting::with('currency')->where('is_active', false)->get();

        if ($settings->isEmpty()) {
            Toast::message('کوین غیرفعالی برای بستن سفارش وجود ندارد.')->warning()->notify();

            return back();
        }

        $cancelled = 0;
        $failed = 0;
        $firstError = null;

        foreach ($settings as $setting) {
            $result = $client->cancelForCurrency($setting->currency_id);
            if ($result['ok']) {
                $cancelled += $result['cancelled'];
                continue;
            }

            $failed++;
            $firstError ??= ($setting->currency->symbol ?? $setting->currency_id).': '.$result['message'];
        }

        if ($failed === 0) {
            Toast::message('سفارش‌های باز ربات برای کوین‌های غیرفعال بسته شد. تعداد: '.$cancelled)->success()->notify();
        } elseif ($cancelled === 0) {
            Toast::message($firstError ?? 'بستن سفارش‌ها انجام نشد.')->danger()->notify();
        } else {
            Toast::message($cancelled.' سفارش بسته شد و '.$failed.' کوین خطا داشت.')->warning()->notify();
        }

        return back();
    }

    public function edit(Request $request, Currency $currency)
    {
        $spotBotSetting = SpotBotSetting::with('fakeUser')->firstOrCreate(['currency_id' => $currency->id]);

        if ($request->ajax()) {
            return view('dashboard.setting.spot-bot._modal_form', compact('currency', 'spotBotSetting'));
        }

        return view('dashboard.setting.spot-bot.edit', compact('currency', 'spotBotSetting'));
    }

    public function update(UpdateSpotBotSettingRequest $request, Currency $currency)
    {
        $data = $request->validated();

        // Ensure is_active is explicitly set as boolean even when checkbox is unchecked
        $data['is_active'] = $request->boolean('is_active');

        SpotBotSetting::updateOrCreate(
            ['currency_id' => $currency->id],
            $data
        );
        Toast::message('تنظیمات با موفقیت ثبت شد.')->success()->notify();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'تنظیمات با موفقیت ثبت شد.']);
        }

        return redirect()->route('admin.setting.spot-bot.index');
    }
}
