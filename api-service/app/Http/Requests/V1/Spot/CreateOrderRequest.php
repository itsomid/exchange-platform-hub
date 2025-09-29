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
     * Before validation, truncate the limit order price based on market's price_precision
     */
    protected function prepareForValidation(): void
    {
        $type = $this->input('type');
        $marketId = $this->input('market_id');
        $price = $this->input('price');

        // Only truncate price with correct precision for limit orders
        if ($type === SpotOrderTypeEnum::LIMIT->value && $price !== null && $marketId) {
            $market = Market::query()->find($marketId);
            $pricePrecision = $market?->currency?->price_precision;

            if ($pricePrecision !== null) {
                $this->merge([
                    'price' => $this->truncateToPrecision((string) $price, (int) $pricePrecision),
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $market = Market::query()->find($this->input('market_id'));
        $amountPrecision = $market?->currency?->amount_precision;

        return [
            'market_id' => ['required', 'integer', Rule::exists(Market::class, 'id')],
            'type' => ['required', Rule::enum(SpotOrderTypeEnum::class)],
            'side' => ['required', Rule::enum(SpotOrderSideEnum::class)],
            'quantity' => [
                'required',
                'numeric',
                'min:' . $market?->min_trade_amount,
                'max:' . $market?->max_trade_amount,
                function (string $attribute, $value, $fail) use ($amountPrecision) {
                    // If amount_precision is defined, check the number of decimal places in the value
                    if ($amountPrecision !== null) {
                        if (!$this->isPrecisionValid((string) $value, (int) $amountPrecision)) {
                            $fail('تعداد اعشار مقدار باید حداکثر ' . $amountPrecision . ' رقم باشد.');
                        }
                    }
                },
            ],
            'price' => ['required_if:type,limit', 'nullable', 'numeric'],
        ];
    }

    /**
     * Truncate a number to specified decimal precision without rounding
     */
    private function truncateToPrecision(string $value, int $precision): string
    {
        // If precision is zero, keep only the integer part
        if ($precision <= 0) {
            $pos = strpos($value, '.');
            return $pos === false ? $value : substr($value, 0, $pos);
        }

        $pos = strpos($value, '.');
        if ($pos === false) {
            // Number without decimal part
            return $value;
        }

        $integer = substr($value, 0, $pos);
        $decimal = substr($value, $pos + 1);
        $decimal = substr($decimal, 0, $precision); // truncate

        return $decimal === '' ? $integer : $integer . '.' . $decimal;
    }

    /**
     * Check if a value's decimal precision is valid against the specified precision
     */
    private function isPrecisionValid(string $value, int $precision): bool
    {
        $pos = strpos($value, '.');
        if ($pos === false) {
            return true; // No decimal part
        }
        $decimal = substr($value, $pos + 1);
        return strlen($decimal) <= $precision;
    }
}
