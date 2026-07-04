<?php

namespace App\Http\Controllers\Bot\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\V1\AcceptTermsRequest;
use App\Http\Resources\Bot\V1\BotUserSettingsResource;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotUserSettings;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    public function show(): \Illuminate\Http\JsonResponse
    {
        $settings = BotUserSettings::firstOrNew(
            ['user_id' => Auth::id()],
            ['auto_trade_enabled' => false, 'reinvest_enabled' => false]
        );

        $global = BotGlobalSettings::current();
        $tiers = $global->transfer_fee_tiers;
        if (empty($tiers)) {
            // Default tiers per RFP (Phase 6 fallback).
            $tiers = [
                ['from' => 20,   'to' => 100,  'fee_type' => 'flat',    'fee_value' => 1],
                ['from' => 100,  'to' => 1000, 'fee_type' => 'percent', 'fee_value' => 1],
                ['from' => 1000, 'to' => null, 'fee_type' => 'flat',    'fee_value' => 12],
            ];
        }

        return response()->json([
            'data' => [
                'terms_accepted'          => $settings->hasAcceptedTerms(),
                'terms_accepted_at'       => $settings->terms_accepted_at?->toIso8601String(),
                'min_deposit_usdt'        => (float) $global->min_deposit_usdt,
                'performance_fee_percent' => (float) $global->performance_fee_percent,
                'transfer_fee_tiers'      => $tiers,
            ],
        ]);
    }

    public function accept(AcceptTermsRequest $request): \Illuminate\Http\JsonResponse
    {
        $settings = BotUserSettings::firstOrCreate(
            ['user_id' => Auth::id()],
            ['auto_trade_enabled' => false, 'reinvest_enabled' => false]
        );

        if (! $settings->hasAcceptedTerms()) {
            $settings->terms_accepted_at = now();
            $settings->save();
        }

        return response()->json([
            'message' => 'شرایط و قوانین با موفقیت پذیرفته شد.',
            'data'    => new BotUserSettingsResource($settings),
        ]);
    }
}
