<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\UpdateBotSettingsRequest;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSignal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () use ($request) {
            $settings = BotGlobalSettings::lockForUpdate()->firstOrNew();
            $wasEnabled = $settings->is_enabled;
            $settings->fill($request->validated());
            $settings->save();

            // Publish the resume edge atomically with the enabled switch.
            // The API scan uses the shared DB markers, not a cross-app cache,
            // to re-evaluate users even when their free balance is unchanged.
            if ($wasEnabled === false && $settings->is_enabled) {
                BotSignal::active()->update(['activation_pending_at' => now()]);
            }
        });

        return redirect()
            ->route('admin.bot.settings.index')
            ->with('success', 'تنظیمات ربات با موفقیت ذخیره شد.');
    }
}
