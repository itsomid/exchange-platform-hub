<?php

namespace App\Http\Requests\Bot\V1;

use Illuminate\Foundation\Http\FormRequest;

class TransferOutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:20'],
        ];
    }
}
