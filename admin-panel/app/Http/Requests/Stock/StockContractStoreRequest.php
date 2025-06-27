<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StockContractStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'stock_id' => 'required|exists:stocks,id',
            'amount' => 'required|numeric|min:1',
            'contract_status' => 'required|in:active,sold,canceled',
            'description' => 'nullable|string',
        ];
    }
}
