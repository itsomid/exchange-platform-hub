<?php

namespace App\Http\Requests\V1\Wallet;

use App\Models\Currency;
use App\Models\CurrencyChain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="GenerateAddressRequest",
 *     type="object",
 *     required={"currency", "chain"},
 *
 *     @OA\Property(
 *         property="currency",
 *         type="string",
 *         example="USDT",
 *         description="The symbol of the currency (e.g., BTC, ETH). Must exist in the currencies table."
 *     ),
 *     @OA\Property(
 *         property="chain",
 *         type="string",
 *         example="TRC20",
 *         description="The chain symbol for the currency. Must exist in the currency chains table with 'deposit_enabled' set to true."
 *     )
 * )
 */
class GenerateAddressRequest extends FormRequest
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
            'currency' => ['required', 'string', Rule::exists(Currency::class, 'symbol')],
            'chain' => ['required', 'string', Rule::exists(CurrencyChain::class, 'chain')->where('deposit_enabled', true)],
        ];
    }
}
