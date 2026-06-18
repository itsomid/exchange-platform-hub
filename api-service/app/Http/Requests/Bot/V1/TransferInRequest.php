<?php

namespace App\Http\Requests\Bot\V1;

use Illuminate\Foundation\Http\FormRequest;

class TransferInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:20'],
        ];
    }
}
