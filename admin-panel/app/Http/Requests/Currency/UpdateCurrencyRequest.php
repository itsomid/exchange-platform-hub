<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
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
            'name' => 'required',
            'persian_name' => 'required',
            'symbol' => 'required|unique:currencies,symbol,'.$this->currency->id,
            'inter_transfer_enabled' => 'boolean',
            'max_auto_withdraw_amount' => 'required',
            'price_precision' => 'required|integer',
            'amount_precision' => 'required|integer',
            'logo' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'is_active' => 'boolean',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'max_auto_withdraw_amount' => str_replace(',', '', $this->max_auto_withdraw_amount)
        ]);

    }
}
