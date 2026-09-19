<?php

namespace App\Http\Requests\Bot\V1;

use Illuminate\Foundation\Http\FormRequest;

class AcceptTermsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'accepted' => ['required', 'accepted'],
        ];
    }
}
