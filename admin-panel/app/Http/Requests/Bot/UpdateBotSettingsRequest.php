<?php

namespace App\Http\Requests\Bot;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bot-management');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_enabled'                      => $this->boolean('is_enabled'),
            'cancel_sell_on_exchange_enabled' => $this->boolean('cancel_sell_on_exchange_enabled'),
        ]);
    }

    public function rules(): array
    {
        return [
            'min_deposit_usdt'          => ['required', 'numeric', 'min:1'],
            'alpha_weight'              => ['required', 'numeric', 'min:0', 'max:1'],
            'default_sell_orders_count' => ['required', 'integer', 'min:1', 'max:10'],
            'performance_fee_percent'   => ['required', 'numeric', 'min:0', 'max:100'],
            'referral_fee_percent'      => ['required', 'numeric', 'min:0', 'lte:performance_fee_percent'],
            'p2p_min_order_value'       => ['required', 'numeric', 'min:0.1', 'max:10000'],
            'precheck_floor_mode'       => ['required', 'in:single,multi'],
            'is_enabled'                => ['boolean'],
            'cancel_sell_on_exchange_enabled' => ['boolean'],

            'transfer_fee_tiers'             => ['required', 'array', 'min:1'],
            'transfer_fee_tiers.*.from'      => ['required', 'numeric', 'min:0'],
            'transfer_fee_tiers.*.to'        => ['nullable', 'numeric'],
            'transfer_fee_tiers.*.fee_type'  => ['required', 'in:flat,percent'],
            'transfer_fee_tiers.*.fee_value' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'min_deposit_usdt'          => 'حداقل واریز (USDT)',
            'alpha_weight'              => 'وزن آلفا',
            'default_sell_orders_count' => 'تعداد پیش‌فرض سفارش فروش',
            'performance_fee_percent'   => 'کارمزد عملکرد (%)',
            'referral_fee_percent'      => 'سهم معرف از کارمزد عملکرد (%)',
            'is_enabled'                => 'وضعیت ربات',
            'cancel_sell_on_exchange_enabled' => 'فروش کوین‌ها روی صرافی مرجع هنگام لغو',
        ];
    }
}
