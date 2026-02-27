<?php

namespace App\Http\Requests\V1\Wallet;

use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Rules\CheckMinAmount;
use App\Rules\CheckOTPRule;
use App\Rules\CheckTwoFactorRule;
use App\Rules\CheckWalletBalance;
use App\Rules\GeneralBlockchainAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="WithdrawRequest",
 *     required={"currency", "currency_chain", "destination_address", "amount", "otp_code"},
 *
 *     @OA\Property(
 *         property="currency",
 *         type="string",
 *         description="The currency symbol to withdraw.",
 *         example="BTC"
 *     ),
 *     @OA\Property(
 *         property="currency_chain",
 *         type="string",
 *         description="The chain of the currency for withdrawal.",
 *         example="BTC"
 *     ),
 *     @OA\Property(
 *         property="destination_address",
 *         type="string",
 *         description="The blockchain address to receive the withdrawal.",
 *         example="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         description="The amount to withdraw.",
 *         example=0.01
 *     ),
 *     @OA\Property(
 *         property="2fa_code",
 *         type="boolean",
 *         description="This field is required only if the user has 2FA enabled.",
 *         example="563242"
 *     ),
 *     @OA\Property(
 *         property="otp_code",
 *         type="string",
 *         description="The OTP code for two-factor authentication.",
 *         example="123456"
 *     ),
 *     @OA\Property(
 *         property="remark",
 *         type="string",
 *         description="Optional user note for the withdrawal.",
 *         example="Payment for services"
 *     )
 * )
 */
class WithdrawRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $currency = $this->input('currency');
        $currencyChain = $this->input('currency_chain');

        $this->merge([
            'currency' => is_string($currency) ? strtoupper(trim($currency)) : $currency,
            'currency_chain' => is_string($currencyChain) ? strtoupper(trim($currencyChain)) : $currencyChain,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currency = Currency::query()->where('symbol', $this->input('currency'))->first(['id']);

        $rules = [
            'currency' => ['required', Rule::exists(Currency::class, 'symbol')],
            'currency_chain' => ['required', Rule::exists(CurrencyChain::class, 'chain')->where('currency_id', $currency?->id)->where('withdraw_enabled', 1)],
            'destination_address' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $chain = $this->input('currency_chain');
                    $validator = new GeneralBlockchainAddress($chain);
                    $validator->validate($attribute, $value, $fail);
                },
            ],
            'amount' => ['required', new CheckMinAmount($this->input('currency'), $this->input('currency_chain')), new CheckWalletBalance($this->input('currency'))],
            'remark' => ['nullable', 'string', 'max:100'],
        ];

        // Skip 2FA and OTP validation on localhost
        if (!app()->environment('local')) {
            $rules['2fa_code'] = [Rule::requiredIf(fn() => ! empty(Auth::user()->two_factor_secret)), new CheckTwoFactorRule];
            $rules['otp_code'] = ['required', new CheckOTPRule];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency.required' => __('validation.required', ['attribute' => 'ارز']),
            'currency.exists' => __('validation.exists', ['attribute' => 'ارز']),
            'currency_chain.required' => __('validation.required', ['attribute' => 'شبکه ارز']),
            'currency_chain.exists' => __('validation.currency_chain_inactive'),
            'destination_address.required' => __('validation.required', ['attribute' => 'آدرس مقصد']),
            'destination_address.string' => __('validation.string', ['attribute' => 'آدرس مقصد']),
            'amount.required' => __('validation.required', ['attribute' => 'مقدار']),
            '2fa_code.required' => __('validation.required', ['attribute' => 'کد Google 2FA']),
            'otp_code.required' => __('validation.required', ['attribute' => 'کد OTP']),
            'remark.string' => __('validation.string', ['attribute' => 'یادداشت']),
            'remark.max' => __('validation.max.string', ['attribute' => 'یادداشت', 'max' => 1000]),
        ];
    }
}
