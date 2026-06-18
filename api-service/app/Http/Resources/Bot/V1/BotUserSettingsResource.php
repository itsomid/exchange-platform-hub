<?php

namespace App\Http\Resources\Bot\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotUserSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'auto_trade_enabled' => (bool) $this->auto_trade_enabled,
            'reinvest_enabled'   => (bool) $this->reinvest_enabled,
            'terms_accepted_at'  => $this->terms_accepted_at?->toIso8601String(),
        ];
    }
}
