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
            'quantity' => 'required|numeric|min:1',
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
            'quantity.required' => 'تعداد سهام الزامی است',
            'quantity.integer' => 'تعداد سهام باید عددی باشد',
            'quantity.min' => 'تعداد سهام باید حداقل 1 باشد',
            'tracking_code.required' => 'کد پیگیری الزامی است',
            'tracking_code.string' => 'کد پیگیری باید متن باشد',
            'tracking_code.max' => 'کد پیگیری نباید بیشتر از 100 کاراکتر باشد',
            'price.required' => 'قیمت الزامی است',
            'price.numeric' => 'قیمت باید عددی باشد',
            'price.min' => 'قیمت باید بیشتر از صفر باشد',
            'description.max' => 'توضیحات نباید بیشتر از 255 کاراکتر باشد'
        ];
    }
}
