<?php

namespace App\Http\Controllers\Bot\V1;

use App\Events\Bot\BotAutoTradeToggled;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\V1\UpdateSettingsRequest;
use App\Http\Resources\Bot\V1\BotUserSettingsResource;
use App\Models\Bot\BotUserSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = BotUserSettings::firstOrCreate(
            ['user_id' => Auth::id()],
            ['auto_trade_enabled' => false, 'reinvest_enabled' => false]
        );

        return response()->json(['data' => new BotUserSettingsResource($settings)]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = BotUserSettings::firstOrCreate(
            ['user_id' => Auth::id()],
            ['auto_trade_enabled' => false, 'reinvest_enabled' => false]
        );

        $previousState = $settings->auto_trade_enabled;
        $settings->auto_trade_enabled = $request->validated('auto_trade_enabled');
        $settings->save();

        if ($previousState !== $settings->auto_trade_enabled) {
            event(new BotAutoTradeToggled(Auth::id(), $settings->auto_trade_enabled));
        }

        return response()->json([
            'message' => 'تنظیمات با موفقیت به‌روز شد.',
            'data'    => new BotUserSettingsResource($settings),
        ]);
    }
}
