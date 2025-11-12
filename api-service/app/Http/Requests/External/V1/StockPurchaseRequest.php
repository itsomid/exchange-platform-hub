<?php

namespace App\Http\Requests\External\V1;

use Illuminate\Foundation\Http\FormRequest;

class StockPurchaseRequest extends FormRequest
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
            'stock_id' => 'required|integer|exists:stocks,id',
            'amount' => 'required|numeric|min:0.01',
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
            'stock_id.required' => 'شناسه سهام الزامی است',
            'stock_id.integer' => 'شناسه سهام باید عددی باشد',
            'stock_id.exists' => 'سهام انتخاب شده معتبر نیست',
            'amount.required' => 'مبلغ خرید الزامی است',
            'amount.numeric' => 'مبلغ خرید باید عددی باشد',
            'amount.min' => 'مبلغ خرید باید حداقل 0.01 دلار باشد',
            'tracking_code.required' => 'کد پیگیری الزامی است',
            'tracking_code.string' => 'کد پیگیری باید متن باشد',
            'tracking_code.max' => 'کد پیگیری نباید بیشتر از 100 کاراکتر باشد',
            'description.max' => 'توضیحات نباید بیشتر از 255 کاراکتر باشد'
        ];
    }
}
