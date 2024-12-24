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
            'currency' => ['required','string','max:10'],
            'description' => ['nullable'],
        ];
    }

    public function messages()
    {
        return [
            'user.required' => 'انتخاب کاربر الزامی است',
            'user.exists' => 'کاربر انتخاب شده موجود نیست',
            'currency.required' => 'انتخاب کوین الزامی است',
            'amount.required' => 'مقدار الزامی است',
            'amount.numeric' => 'مقدار باید به صورت عددی باشد',
        ];
    }
}
