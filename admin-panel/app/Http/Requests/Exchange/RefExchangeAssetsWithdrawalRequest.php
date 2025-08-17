<?php

namespace App\Http\Requests\Exchange;

use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\Exchange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefExchangeAssetsWithdrawalRequest extends FormRequest
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
            'exchange_slug' => ['required', Rule::exists(Exchange::class, 'slug')],
            'currency_symbol' => ['required', Rule::exists(Currency::class, 'symbol')],
            'currency_chain' => ['required', Rule::exists(CurrencyChain::class, 'chain')],
            'amount' => ['required', 'numeric'],
            'withdrawal_address' => 'required',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove commas from amount field to make it numeric
        if ($this->has('amount')) {
            $amount = $this->input('amount');
            if (is_string($amount)) {
                // Remove commas and convert to numeric format
                $cleanedAmount = str_replace(',', '', $amount);
                $this->merge(['amount' => $cleanedAmount]);
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'exchange_slug.required' => 'انتخاب صرافی الزامی است.',
            'exchange_slug.exists' => 'صرافی انتخاب شده معتبر نیست.',
            'currency_symbol.required' => 'انتخاب ارز الزامی است.',
            'currency_symbol.exists' => 'ارز انتخاب شده معتبر نیست.',
            'currency_chain.required' => 'انتخاب شبکه الزامی است.',
            'currency_chain.exists' => 'شبکه انتخاب شده معتبر نیست.',
            'amount.required' => 'مقدار برداشت الزامی است.',
            'amount.numeric' => 'مقدار برداشت باید عددی باشد.',
            'withdrawal_address.required' => 'آدرس برداشت الزامی است.',
        ];
    }
}
