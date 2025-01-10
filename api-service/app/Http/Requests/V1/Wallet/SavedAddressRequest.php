<?php

namespace App\Http\Requests\V1\Wallet;

use App\Models\CurrencyChain;
use App\Models\SavedAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="SavedAddressRequest",
 *     required={"name", "address", "chain"},
 *
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="The symbol of the currency to refresh.",
 *         example="Father Wallet"
 *     ),
 *     @OA\Property(
 *         property="address",
 *         type="string",
 *         description="The wallet address.",
 *         example="0x7dc527c027cf4babc67e6988d2d61e5bd63f7033"
 *     ),
 *     @OA\Property(
 *     property="chain",
 *     type="string",
 *     description="The blockchain chain for the wallet address.",
 *     example="BSC"
 *      )
 * )
 */
class SavedAddressRequest extends FormRequest
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
            'name' => ['required', Rule::unique(SavedAddress::class, 'name')],
            'address' => ['required'],
            'chain' => ['required', Rule::exists(CurrencyChain::class, 'chain')],
        ];
    }
}
