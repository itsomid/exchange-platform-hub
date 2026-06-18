<?php

namespace App\Http\Requests\Bot\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to'   => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }
}
