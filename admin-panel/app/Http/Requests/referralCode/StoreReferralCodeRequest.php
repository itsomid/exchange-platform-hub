<?php

namespace App\Http\Requests\referralCode;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferralCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'unique:referral_codes,code', 'max:255'],
            'introducer_fee' => ['required', 'integer', 'min:0', 'max:30'], // e.g., max introducer fee = 30%
            'friend_fee' => ['required', 'integer', 'min:0', 'max:30'], // e.g., max friend fee = 30%
            'usage_limit' => ['nullable', 'integer', 'min:1'], // Optional, at least 1 usage allowed
        ];
    }
}
