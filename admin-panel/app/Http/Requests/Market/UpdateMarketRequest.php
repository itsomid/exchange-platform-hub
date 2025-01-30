<?php

namespace App\Http\Requests\Market;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketRequest extends FormRequest
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
            'min_otc_amount' => ['required', 'numeric', 'min:0'],
            'max_otc_amount' => ['required', 'numeric', 'gt:min_otc_amount'],
            'exchange_profit_sell'   => ['required', 'numeric'],
            'exchange_profit_buy'   => ['required', 'numeric'],
            'is_active'        => ['nullable', 'boolean'],
            'exchange_id' => ['required', 'numeric', 'exists:exchanges,id'],
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'min_otc_amount' => str_replace(',', '', $this->min_otc_amount),
            'max_otc_amount' => str_replace(',', '', $this->max_otc_amount),
        ]);
    }
}
