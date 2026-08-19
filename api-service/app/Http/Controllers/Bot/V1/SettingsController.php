<?php

namespace App\Http\Controllers\Bot\V1;

use App\Events\Bot\BotAutoTradeToggled;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\V1\UpdateSettingsRequest;
use App\Http\Resources\Bot\V1\BotUserSettingsResource;
use App\Models\Bot\BotUserSettings;
use App\Services\Bot\BotAutoTradeToggleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct(private readonly BotAutoTradeToggleService $toggle) {}

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
        $enabled = $request->boolean('auto_trade_enabled');
        $changed = $this->toggle->setByUser(Auth::user(), $enabled);

        if ($changed) {
            event(new BotAutoTradeToggled(Auth::id(), $enabled));
        }

        $settings = BotUserSettings::where('user_id', Auth::id())->first();

        return response()->json([
            'message' => 'تنظیمات با موفقیت به‌روز شد.',
            'data'    => new BotUserSettingsResource($settings),
        ]);
    }
}
