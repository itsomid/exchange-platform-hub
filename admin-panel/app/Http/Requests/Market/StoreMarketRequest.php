<?php

namespace App\Http\Requests\Market;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketRequest extends FormRequest
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
            'exchange_id' => 'required|exists:exchanges,id',
            'symbol' => 'required|exists:currencies,id',
            'min_otc_amount' => 'required|numeric|min:0',
            'max_otc_amount' => 'required|numeric|gt:min_otc_amount',
            'min_trade_amount' => 'required|numeric|min:0',
            'max_trade_amount' => 'required|numeric|gt:min_trade_amount',
            'exchange_profit_sell' => 'required|numeric|min:0',
            'exchange_profit_buy' => 'required|numeric',
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'min_otc_amount' => str_replace(',', '', $this->min_otc_amount),
            'max_otc_amount' => str_replace(',', '', $this->max_otc_amount),
            'min_trade_amount' => str_replace(',', '', $this->min_trade_amount),
            'max_trade_amount' => str_replace(',', '', $this->max_trade_amount),
        ]);
    }
}
