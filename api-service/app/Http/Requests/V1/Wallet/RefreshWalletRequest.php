<?php

namespace App\Http\Requests\V1\Wallet;

use App\Models\Currency;
use App\Models\CurrencyChain;
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
 *     ),
 *     @OA\Property(
 *         property="chain_symbol",
 *         type="string",
 *         description="The symbol of the chain for the specified currency.",
 *         example="BSC"
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
        $currency = Currency::query()->where('symbol', $this->input('currency_symbol'))->first();

        return [
            'currency_symbol' => ['required', Rule::exists(Currency::class, 'symbol')],
            'chain_symbol' => ['required', Rule::exists(CurrencyChain::class, 'chain')->where('currency_id', $currency?->id)],
        ];
    }
}
