<?php

namespace App\Http\Requests\V1\OTC;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AvailableCoinRequest",
 *     type="object",
 *     required={"market_id"},
 *     title="Available Coin Request",
 *     description="Request payload to fetch available coins for a specific market.",
 *
 *     @OA\Property(
 *         property="market_id",
 *         type="integer",
 *         description="The ID of the market.",
 *         example=1
 *     )
 * )
 */
class AvailableBalanceRequest extends FormRequest
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
        ];
    }
}
