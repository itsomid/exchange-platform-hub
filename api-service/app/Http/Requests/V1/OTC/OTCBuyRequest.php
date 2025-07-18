<?php

namespace App\Http\Requests\V1\OTC;

use App\Exceptions\V1\OTC\MaxOTCAmountException;
use App\Exceptions\V1\OTC\MinOTCAmountException;
use App\Models\Market;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="OTCBuyRequest",
 *     type="object",
 *     required={"market_id", "quantity"},
 *
 *     @OA\Property(
 *         property="market_id",
 *         type="integer",
 *         description="The ID of the market where the coin will be bought.",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="quantity",
 *         type="number",
 *         format="float",
 *         description="The quantity of the coin to buy.",
 *         example=10.5
 *     )
 * )
 */
class OTCBuyRequest extends FormRequest
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
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'quantity' => ['required', 'numeric'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $marketId = $this->input('market_id');
            $quantity = $this->input('quantity');

            if ($marketId && $quantity) {
                $market = Market::find($marketId);

                if ($market) {
                    if ($quantity < $market->min_otc_amount) {
                        throw new MinOTCAmountException("The quantity must be at least {$market->min_otc_amount}.");
                    }

                    if ($quantity > $market->max_otc_amount) {
                        throw new MaxOTCAmountException("The quantity may not be greater than {$market->max_otc_amount}.");
                    }
                }
            }
        });
    }
}
