<?php

namespace App\Http\Requests\V1\Wallet;

use App\Models\CurrencyChain;
use App\Models\SavedAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavedAddressRequest extends FormRequest
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
            'name' => ['required', Rule::unique(SavedAddress::class, 'name')],
            'address' => ['required'],
            'chain' => ['required', Rule::exists(CurrencyChain::class, 'chain')],
        ];
    }
}
