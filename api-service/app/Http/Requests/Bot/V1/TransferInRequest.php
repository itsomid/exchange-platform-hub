<?php

namespace App\Http\Requests\Bot\V1;

use App\Models\Bot\BotGlobalSettings;
use Illuminate\Foundation\Http\FormRequest;

class TransferInRequest extends FormRequest
{
    public function rules(): array
    {
        $minDeposit = BotGlobalSettings::current()->min_deposit_usdt;

        return [
            'amount' => ['required', 'numeric', 'min:'.$minDeposit],
        ];
    }
}
