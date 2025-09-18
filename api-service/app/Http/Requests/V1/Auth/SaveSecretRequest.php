<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SaveSecretRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '2fa' => ['required'],
            'secret' => ['required', function ($attribute, $value, $fail) {
                if (! empty(Auth::user()->two_factor_secret)) {
                    $fail('You have already set up 2FA.');
                }
            }],
        ];
    }
}
