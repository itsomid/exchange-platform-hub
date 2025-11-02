<?php

namespace App\Http\Requests\External\V1;

use Illuminate\Foundation\Http\FormRequest;

class UserCreditTransactionsRequest extends FormRequest
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
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'tracking_code' => 'nullable|string|max:100',
            'id' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'شماره صفحه باید عددی باشد',
            'page.min' => 'شماره صفحه باید بیشتر از صفر باشد',
            'per_page.integer' => 'تعداد آیتم در صفحه باید عددی باشد',
            'per_page.min' => 'تعداد آیتم در صفحه باید بیشتر از صفر باشد',
            'per_page.max' => 'تعداد آیتم در صفحه نباید بیشتر از 100 باشد',
            'date_from.date' => 'فرمت تاریخ شروع صحیح نیست',
            'date_to.date' => 'فرمت تاریخ پایان صحیح نیست',
            'date_to.after_or_equal' => 'تاریخ پایان باید بعد از تاریخ شروع باشد',
            'tracking_code.string' => 'کد پیگیری باید متن باشد',
            'tracking_code.max' => 'کد پیگیری نباید بیشتر از 100 کاراکتر باشد',
            'id.string' => 'شناسه باید کاراکتر باشد',
            'id.max' => 'شناسه نباید بیشتر از 100 کاراکتر باشد',
        ];
    }
}
