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
        $currencies = Currency::with('spotBotSetting')->get();
        return view('dashboard.setting.spot-bot.index', compact('currencies'));
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
