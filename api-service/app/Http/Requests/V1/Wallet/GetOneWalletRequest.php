<?php

namespace App\Http\Requests\V1\Wallet;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetOneWalletRequest extends FormRequest
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
            'currencySymbol' => ['required', Rule::exists(Currency::class, 'symbol')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currencySymbol' => $this->route('currencySymbol'),
        ]);
    }
}
