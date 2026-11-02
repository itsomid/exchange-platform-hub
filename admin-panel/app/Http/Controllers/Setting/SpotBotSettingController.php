<?php

namespace App\Http\Controllers\Setting;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSpotBotSettingRequest;
use App\Models\Currency;
use App\Models\SpotBotSetting;
use App\Models\User;
use Illuminate\Http\Request;

class SpotBotSettingController extends Controller
{
    public function index()
    {
        $totalCurrencies = Currency::count();
        $activeBots = SpotBotSetting::where('is_active', true)->count();
        $configuredBots = SpotBotSetting::count();
        $inactiveBots = $totalCurrencies - $activeBots;

        $currencies = Currency::with(['spotBotSetting.fakeUser'])
            ->orderBy('symbol')
            ->paginate(20);

        return view('dashboard.setting.spot-bot.index', compact(
            'currencies',
            'totalCurrencies',
            'activeBots',
            'inactiveBots',
            'configuredBots',
        ));
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
