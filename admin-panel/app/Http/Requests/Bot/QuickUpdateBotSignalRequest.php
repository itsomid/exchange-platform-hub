<?php

namespace App\Http\Requests\Bot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuickUpdateBotSignalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bot-management');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'floor_price'   => str_replace(',', '', (string) $this->floor_price),
            'ceiling_price' => str_replace(',', '', (string) $this->ceiling_price),
        ]);
    }

    public function rules(): array
    {
        return [
            'floor_price'            => ['required', 'numeric', 'min:0'],
            'ceiling_price'          => ['required', 'numeric', 'min:0'],
            'max_allocation_percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'sell_mode'              => ['required', 'in:percent,price'],
            'sell_targets'           => ['required', 'array', 'min:1', 'max:10'],
            'sell_targets.*.trigger' => ['required', 'numeric', 'min:0'],
            'sell_targets.*.share'   => ['required', 'numeric', 'min:0.01', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $floor   = (float) $this->input('floor_price', 0);
            $ceiling = (float) $this->input('ceiling_price', 0);

            if ($ceiling > 0 && $floor >= $ceiling) {
                $v->errors()->add('floor_price', 'قیمت کف باید کمتر از قیمت سقف باشد.');
            }

            $targets = $this->input('sell_targets', []);
            if (is_array($targets)) {
                $total = array_sum(array_column($targets, 'share'));
                if (abs($total - 100) > 0.01) {
                    $v->errors()->add('sell_targets', 'مجموع درصد اشتراک سفارشات فروش باید دقیقاً ۱۰۰٪ باشد (مقدار فعلی: ' . $total . '٪).');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'floor_price'            => 'قیمت کف',
            'ceiling_price'          => 'قیمت سقف',
            'max_allocation_percent' => 'سقف تخصیص (%)',
            'sell_mode'              => 'نوع هدف فروش',
            'sell_targets'           => 'اهداف فروش',
        ];
    }
}
