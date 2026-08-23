<?php

namespace App\Http\Requests\Accounting\V1;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $referenceIdRules = ['sometimes', 'integer', 'min:1'];

        return [
            'from_id' => ['sometimes', 'integer', 'min:1'],
            'to_id' => ['sometimes', 'integer', 'min:1', Rule::when($this->filled('from_id'), ['gte:from_id'])],
            'type' => ['sometimes', 'string', Rule::enum(TransactionTypeEnum::class)],
            'subtype' => ['sometimes', 'string', Rule::enum(TransactionSubTypeEnum::class)],
            'status' => ['sometimes', 'string', Rule::enum(TransactionStatusEnum::class)],
            'date' => ['sometimes', 'date', 'prohibits:date_from,date_to'],
            'date_from' => ['sometimes', 'date', 'prohibits:date'],
            'date_to' => ['sometimes', 'date', 'prohibits:date', Rule::when($this->filled('date_from'), ['after_or_equal:date_from'])],
            'deposit_id' => $referenceIdRules,
            'withdrawal_id' => $referenceIdRules,
            'otc_order_id' => $referenceIdRules,
            'spot_trade_id' => $referenceIdRules,
            'stock_contract_id' => $referenceIdRules,
            'bot_order_id' => $referenceIdRules,
            'bot_buy_execution_id' => $referenceIdRules,
            'bot_wallet_transfer_id' => $referenceIdRules,
        ];
    }
}
