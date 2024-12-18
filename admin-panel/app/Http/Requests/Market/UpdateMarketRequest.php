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
            'min_trade_amount' => ['required', 'numeric', 'min:0'],
            'max_trade_amount' => ['required', 'numeric', 'gt:min_trade_amount'],
            'exchange_profit'   => ['required', 'numeric', 'min:0'],
            'is_active'        => ['nullable', 'boolean'],
            'exchange_id' => ['required', 'numeric', 'exists:exchanges,id'],
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'min_trade_amount' => str_replace(',', '', $this->min_trade_amount),
            'max_trade_amount' => str_replace(',', '', $this->max_trade_amount),
            'exchange_profit' => str_replace(',', '', $this->exchange_profit),

        ]);
    }
}
