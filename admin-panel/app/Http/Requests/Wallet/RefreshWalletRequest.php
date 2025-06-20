<?php

namespace App\Http\Requests\Wallet;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="RefreshWalletRequest",
 *     required={"currency_symbol", "chain_symbol"},
 *
 *     @OA\Property(
 *         property="currency_symbol",
 *         type="string",
 *         description="The symbol of the currency to refresh.",
 *         example="BNB"
 *     )
 * )
 */
class RefreshWalletRequest extends FormRequest
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
            'currency_symbol' => ['required', Rule::exists(Currency::class, 'symbol')],
        ];
    }
}
