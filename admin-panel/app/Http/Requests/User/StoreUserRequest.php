<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'username' => 'string|unique:users,username|max:255',
            'mobile' => 'nullable|string|unique:users,mobile|max:15',
            'status' => 'required|in:active,suspended,inactive',
            'password' => 'required|string|min:8',
            'kyc_status' => 'required|in:pending,approved,rejected',
            //            'introducer_code' => 'nullable|exists:referral_codes,code',
            'description' => 'nullable|string|max:65535',
        ];
    }
}
