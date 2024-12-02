<?php

namespace App\Http\Requests\V1\Currency;

use Illuminate\Foundation\Http\FormRequest;
/**
 * @OA\Schema(
 *     schema="CurrencyConfigRequest",
 *     type="object",
 *     required={"ccy"},
 *     @OA\Property(
 *         property="ccy",
 *         type="string",
 *         example="BTC",
 *         description="The symbol of the currency (e.g., BTC, ETH). Must exist in the currencies table."
 *     )
 * )
 */
class CurrencyConfigRequest extends FormRequest
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
            'ccy' => ['required', 'string', 'exists:currencies,symbol'],
        ];
    }
}
