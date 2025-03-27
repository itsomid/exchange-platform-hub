<?php

namespace App\Http\Requests\V1\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrderRequest extends FormRequest
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
            'type' => ['sometimes', 'string', Rule::enum(SpotOrderTypeEnum::class)],
            'side' => ['sometimes', 'string', Rule::enum(SpotOrderSideEnum::class)],
            'status' => ['sometimes', 'string', Rule::enum(SpotOrderStatusEnum::class)],
        ];
    }
}
