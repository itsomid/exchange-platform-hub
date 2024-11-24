<?php

namespace App\Http\Requests\referralCode;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReferralCodeRequest extends FormRequest
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
//        $referralCodeId = $this->route('referral_code');
        return [
            'code' => ['required', 'string', 'max:255', "unique:referral_codes,code,{$this->referral_code->id}"],
            'introducer_fee' => ['required', 'integer', 'min:0', 'max:30'], // Max introducer fee is 30%
            'friend_fee' => ['required', 'integer', 'min:0', 'max:30'], // Max friend fee is 30%
            'usage_limit' => ['nullable', 'integer', 'min:1'], // Optional, must be >= 1 if provided
        ];
    }
}
