<?php

namespace App\Http\Controllers\Setting;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\SpotBotSetting;
use App\Models\User;
use Illuminate\Http\Request;

class SpotBotSettingController extends Controller
{
    public function index()
    {
        $currencies = Currency::with('spotBotSetting')->get();
        return view('dashboard.setting.spot-bot.index', compact('currencies'));
    }

    public function edit(Currency $currency)
    {
        $spotBotSetting = SpotBotSetting::with('fakeUser')->firstOrCreate(['currency_id' => $currency->id]);
        return view('dashboard.setting.spot-bot.edit', compact('currency', 'spotBotSetting'));
    }

    public function update(Request $request, Currency $currency)
    {
        $data = $request->validate([
            'is_active' => 'boolean',
            'price_interval_seconds' => 'required|integer|min:1',
            'order_margin' => 'required|numeric|min:0',
            'buy_orders_count' => 'required|integer|min:0',
            'sell_orders_count' => 'required|integer|min:0',
            'fake_user_id' => 'nullable|exists:users,id',
            'market_crash_percentage' => 'nullable|numeric|min:0|max:100',
            'min_order_size' => 'nullable|numeric|min:0',
            'max_order_size' => 'nullable|numeric|min:0',
        ]);

        SpotBotSetting::updateOrCreate(
            ['currency_id' => $currency->id],
            $data
        );
        Toast::message('تنظیمات با موفقیت ثبت شد.')->success()->notify();
        return redirect()->route('admin.setting.spot-bot.index');
    }
}
