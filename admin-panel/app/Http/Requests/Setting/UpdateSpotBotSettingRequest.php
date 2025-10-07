<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpotBotSettingRequest extends FormRequest
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
            'is_active' => 'boolean',
            'price_interval_seconds' => 'required|integer|min:1',
            'order_margin' => 'required|numeric|min:0',
            'buy_orders_count' => 'required|integer|min:0',
            'sell_orders_count' => 'required|integer|min:0',
            'fake_user_id' => 'nullable|exists:users,id',
            'market_crash_percentage' => 'nullable|numeric|min:0|max:100',
            'min_order_size' => 'nullable|numeric|min:0',
            'max_order_size' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation()
    {
        $data = [];

        // Remove commas from min_order_size if present
        if ($this->has('min_order_size') && !is_null($this->min_order_size)) {
            $data['min_order_size'] = str_replace(',', '', $this->min_order_size);
        }

        // Remove commas from max_order_size if present
        if ($this->has('max_order_size') && !is_null($this->max_order_size)) {
            $data['max_order_size'] = str_replace(',', '', $this->max_order_size);
        }

        // Merge the processed data
        if (!empty($data)) {
            $this->merge($data);
        }
    }
}