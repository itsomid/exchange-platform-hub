<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StockContractUpdateRequest extends FormRequest
{
    public function authorize()
    {
        // Adjust authorization logic as needed
        return true;
    }

    public function rules()
    {
        return [
            'contract_status' => 'required|in:active,canceled',
            'description' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'contract_status.required' => 'وضعیت قرارداد الزامی است',
            'contract_status.in' => 'وضعیت قرارداد معتبر نیست',
        ];
    }
}
