<?php

namespace App\Http\Requests\V1\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StockPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_id' => ['required', 'integer', 'exists:stocks,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
        ];
    }
} 