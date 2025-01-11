<?php

namespace App\Http\Requests\V1\Transaction;

use App\Enums\TransactionTypeEnum;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="AllDepositWithdrawalRequest",
 *     type="object",
 *     description="Query parameters for filtering deposit and withdrawal transactions.",
 *
 *     @OA\Property(
 *         property="ccy",
 *         type="string",
 *         description="The currency symbol for filtering transactions.",
 *         example="BTC"
 *     ),
 *     @OA\Property(
 *         property="transaction_type",
 *         type="string",
 *         description="The type of transaction to filter (e.g., deposit, withdrawal).",
 *         enum={"deposit", "withdrawal"},
 *         example="deposit"
 *     )
 * )
 */
class AllDepositWithdrawalRequest extends FormRequest
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
            'ccy' => ['sometimes', Rule::exists(Currency::class, 'symbol')],
            'transaction_type' => ['sometimes', Rule::in([TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::WITHDRAWAL->value])],
        ];
    }
}
