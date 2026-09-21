<?php

namespace App\Http\Requests\Market;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exchange_id' => ['required', 'integer', 'exists:exchanges,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'exchange_id.required' => 'صرافی مرجع را انتخاب کنید.',
            'exchange_id.exists' => 'صرافی انتخاب‌شده معتبر نیست.',
        ];
    }
}
