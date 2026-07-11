<?php

namespace App\Http\Requests\Bot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBotSignalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bot-management');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'floor_price'                  => str_replace(',', '', $this->floor_price),
            'ceiling_price'                => str_replace(',', '', $this->ceiling_price),
            'min_buy_amount_usdt'          => str_replace(',', '', $this->min_buy_amount_usdt),
            'p2p_min_order_value_override' => $this->filled('p2p_min_order_value_override')
                ? str_replace(',', '', $this->p2p_min_order_value_override)
                : $this->p2p_min_order_value_override,
        ]);
    }

    public function rules(): array
    {
        return [
            'currency_id'                  => ['required', 'integer', 'exists:currencies,id', 'unique:bot_signals,currency_id'],
            'floor_price'                  => ['required', 'numeric', 'min:0'],
            'ceiling_price'                => ['required', 'numeric', 'min:0'],
            'min_buy_amount_usdt'          => ['required', 'numeric', 'min:0'],
            'max_allocation_percent'       => ['required', 'numeric', 'min:0.01', 'max:100'],
            'p2p_min_order_value_override' => ['nullable', 'numeric', 'min:0.1', 'max:10000'],
            'sell_orders_count'            => ['required', 'integer', 'min:1', 'max:10'],
            'sell_mode'                    => ['required', 'in:percent,price'],
            'is_active'                    => ['boolean'],

            'sell_targets'           => ['required', 'array', 'min:1'],
            'sell_targets.*.trigger' => ['required', 'numeric', 'min:0'],
            'sell_targets.*.share'   => ['required', 'numeric', 'min:0.01', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $this->validateFloorCeiling($v);
            $this->validateSellTargetShareSum($v);
        });
    }

    private function validateFloorCeiling(Validator $v): void
    {
        $floor   = (float) $this->input('floor_price', 0);
        $ceiling = (float) $this->input('ceiling_price', 0);

        if ($ceiling > 0 && $floor >= $ceiling) {
            $v->errors()->add('floor_price', 'قیمت کف باید کمتر از قیمت سقف باشد.');
        }
    }

    private function validateSellTargetShareSum(Validator $v): void
    {
        $targets = $this->input('sell_targets', []);
        if (! is_array($targets)) {
            return;
        }

        $total = array_sum(array_column($targets, 'share'));
        if (abs($total - 100) > 0.01) {
            $v->errors()->add('sell_targets', 'مجموع درصد اشتراک سفارشات فروش باید دقیقاً ۱۰۰٪ باشد (مقدار فعلی: ' . $total . '٪).');
        }
    }

    public function attributes(): array
    {
        return [
            'currency_id'            => 'ارز',
            'floor_price'            => 'قیمت کف',
            'ceiling_price'          => 'قیمت سقف',
            'min_buy_amount_usdt'    => 'حداقل مبلغ خرید (USDT)',
            'max_allocation_percent' => 'سقف تخصیص (%)',
            'sell_orders_count'      => 'تعداد سفارش فروش',
            'sell_mode'              => 'نوع هدف فروش',
            'sell_targets'           => 'اهداف فروش',
        ];
    }
}
