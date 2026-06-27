<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncreaseCreditRequest extends FormRequest
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
            'amount' => ['required', 'numeric'],
            'user' => ['required', 'exists:users,id'],
            'currency_id' => ['required','integer','exists:currencies,id'],
            'chain' => ['nullable','string','max:10'],
            'transaction_hash' => ['nullable', 'unique:withdrawals,transaction_hash'],
            'description' => ['nullable'],
            'admin_description' => ['nullable'],
        ];
    }

    public function messages()
    {
        return [
            'user.required' => 'انتخاب کاربر الزامی است',
            'user.exists' => 'کاربر انتخاب شده موجود نیست',
            'currency_id.required' => 'انتخاب کوین الزامی است',
            'currency_id.exists' => 'کوین انتخاب شده معتبر نیست',
            'chain.required' => 'انتخاب شبکه الزامی است',
            'amount.required' => 'مقدار الزامی است',
            'amount.numeric' => 'مقدار باید به صورت عددی باشد',
        ];
    }
}
