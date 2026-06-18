<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\UpdateBotSettingsRequest;
use App\Models\Bot\BotGlobalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BotSettingsController extends Controller
{
    public function index(): View
    {
        $settings = BotGlobalSettings::first();

        return view('dashboard.bot.settings.index', compact('settings'));
    }

    public function update(UpdateBotSettingsRequest $request): RedirectResponse
    {
        $settings = BotGlobalSettings::firstOrNew();
        $settings->fill($request->validated());
        $settings->save();

        return redirect()
            ->route('admin.bot.settings.index')
            ->with('success', 'تنظیمات ربات با موفقیت ذخیره شد.');
    }
}
