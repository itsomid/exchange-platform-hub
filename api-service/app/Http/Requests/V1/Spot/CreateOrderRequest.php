<?php

namespace App\Http\Requests\V1\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Models\Market;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'quantity' => ['required', 'numeric', 'min:'.$market?->min_trade_amount, 'max:'.$market->max_trade_amount],
            'price' => ['required', 'numeric'],
        ];
    }
}
