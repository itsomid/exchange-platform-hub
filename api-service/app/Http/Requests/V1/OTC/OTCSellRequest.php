<?php

namespace App\Http\Requests\V1\OTC;

use App\Helpers\Math;
use App\Models\Market;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="OTCSellRequest",
 *     type="object",
 *     required={"market_id", "quantity"},
 *
 *     @OA\Property(
 *         property="market_id",
 *         type="integer",
 *         description="The ID of the market where the coin will be sold.",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="quantity",
 *         type="number",
 *         format="float",
 *         description="The quantity of the coin to sell.",
 *         example=10.5
 *     )
 * )
 */
class OTCSellRequest extends FormRequest
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
        $market = Market::query()->find($this->input('market_id'));

        return [
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'quantity' => array_merge(
                ['required', 'numeric', 'gt:0'],
                $market ? ['min:'.$market->min_otc_amount, 'max:'.$market->max_otc_amount] : []
            ),
        ];
        //        return [
        //            'market_id' => ['required', 'integer', 'exists:markets,id'],
        //            array_merge(
        //                ['required', 'numeric'],
        //                $market ? ['min:'.$market->min_otc_amount, 'max:'.$market->max_otc_amount] : []
        //            ),
        //            'quantity' => ['required', 'numeric', function ($attribute, $value, $fail) {
        //                $market = Market::query()->find($this->input('market_id'));
        //                $usdtValue = Math::mul($market->exchangePrice->sell_price, request('quantity'));
        //                if ($usdtValue < 2) {
        //                    $fail(__('validation.min.numeric', ['attribute' => __('validation.attributes.quantity'), 'min' => '2 USDT']));
        //                }
        //            }],
        //        ];
    }
}
