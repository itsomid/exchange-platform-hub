<?php

namespace App\Http\Controllers\SpotBot;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\SpotBotSetting;
use Illuminate\Http\JsonResponse;

class SpotBotSettingController extends Controller
{
    /**
     * Get all bot settings
     *
     * @return JsonResponse
     */
    public function getAllSettings(): JsonResponse
    {
        $settings = SpotBotSetting::with(['currency', 'fakeUser'])->get();

        // Add currency_symbol to each setting
        $settings->each(function ($setting) {
            $setting->currency_symbol = $setting->currency ? $setting->currency->symbol : null;
        });

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Get bot settings for specific currency
     *
     * @param int $currency_id
     * @return JsonResponse
     */
    public function getSettings(int $currency_id): JsonResponse
    {
        $currency = Currency::find($currency_id);
        
        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found'
            ], 404);
        }

        $setting = SpotBotSetting::with(['currency', 'fakeUser'])
            ->where('currency_id', $currency_id)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Bot settings not found for this currency'
            ], 404);
        }

        // Add currency_symbol to the setting
        $setting->currency_symbol = $setting->currency ? $setting->currency->symbol : null;

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }
}