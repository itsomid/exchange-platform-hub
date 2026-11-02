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

        $currencies = Currency::with(['spotBotSetting.fakeUser']);

        if ($sort === 'popular') {
            $currencies->leftJoinSub($this->userTradeCounts(), 'user_trades', function ($join) {
                $join->on('user_trades.symbol', '=', 'currencies.symbol');
            })
                ->select('currencies.*')
                ->selectRaw('COALESCE(user_trades.trades_count, 0) as user_trades_count')
                ->orderByRaw('COALESCE(user_trades.trades_count, 0) DESC')
                ->orderBy('currencies.symbol');
        } else {
            $currencies->orderBy('symbol');
        }

        $currencies = $currencies->paginate(20)->withQueryString();

        return view('dashboard.setting.spot-bot.index', compact(
            'currencies',
            'totalCurrencies',
            'activeBots',
            'inactiveBots',
            'configuredBots',
            'sort',
        ));
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

    public function edit(Currency $currency)
    {
        $spotBotSetting = SpotBotSetting::with('fakeUser')->firstOrCreate(['currency_id' => $currency->id]);
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
        return redirect()->route('admin.setting.spot-bot.index');
    }
}
