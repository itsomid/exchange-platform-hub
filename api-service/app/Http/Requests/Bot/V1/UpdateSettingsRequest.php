<?php

namespace App\Http\Requests\Bot\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'auto_trade_enabled' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->has('reinvest_enabled')) {
                $v->errors()->add('reinvest_enabled', 'تنظیم سرمایه‌گذاری مجدد در این مرحله پشتیبانی نمی‌شود.');
            }
        });
    }
}
