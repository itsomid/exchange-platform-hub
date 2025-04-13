<?php

namespace App\Http\Requests\V1\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Models\Market;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="OrderRequest",
 *     required={"market_id", "type", "side", "quantity", "price"},
 *
 *     @OA\Property(
 *         property="market_id",
 *         type="integer",
 *         example=1,
 *         description="ID of the trading pair market"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"limit", "market"},
 *         example="limit",
 *         description="Order type"
 *     ),
 *     @OA\Property(
 *         property="side",
 *         type="string",
 *         enum={"buy", "sell"},
 *         example="buy",
 *         description="Order side"
 *     ),
 *     @OA\Property(
 *         property="quantity",
 *         type="number",
 *         format="float",
 *         example=0.5,
 *         description="Order quantity (must be between market's min/max trade amount)"
 *     ),
 *     @OA\Property(
 *         property="price",
 *         type="number",
 *         format="float",
 *         example=45000.50,
 *         description="Order price per unit"
 *     )
 * )
 */
class CreateOrderRequest extends FormRequest
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
            'market_id' => ['required', 'integer', Rule::exists(Market::class, 'id')],
            'type' => ['required', Rule::enum(SpotOrderTypeEnum::class)],
            'side' => ['required', Rule::enum(SpotOrderSideEnum::class)],
            'quantity' => ['required', 'numeric', 'min:'.$market?->min_trade_amount, 'max:'.$market?->max_trade_amount],
            'price' => ['required_if:type,limit', 'nullable', 'numeric'],
        ];
    }
}
