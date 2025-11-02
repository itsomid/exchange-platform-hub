<?php

namespace App\Http\Requests\External\V1;

use Illuminate\Foundation\Http\FormRequest;

class UserCreditIncreaseRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'currency' => 'required|string|exists:currencies,symbol',
            'amount' => 'required|numeric|min:0.00000001',
            'tracking_code' => 'required|string|max:100',
            'description' => 'nullable|string|max:255'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'ایمیل کاربر الزامی است',
            'email.email' => 'فرمت ایمیل صحیح نیست',
            'email.exists' => 'کاربری با این ایمیل یافت نشد',
            'currency.required' => 'نوع ارز الزامی است',
            'currency.exists' => 'ارز انتخاب شده معتبر نیست',
            'amount.required' => 'مقدار الزامی است',
            'amount.numeric' => 'مقدار باید عددی باشد',
            'amount.min' => 'مقدار باید بیشتر از صفر باشد',
            'tracking_code.required' => 'کد پیگیری الزامی است',
            'tracking_code.string' => 'کد پیگیری باید متن باشد',
            'tracking_code.max' => 'کد پیگیری نباید بیشتر از 100 کاراکتر باشد',
            'description.max' => 'توضیحات نباید بیشتر از 255 کاراکتر باشد'
        ];
    }
}
