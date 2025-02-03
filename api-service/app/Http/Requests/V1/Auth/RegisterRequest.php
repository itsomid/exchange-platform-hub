<?php

namespace App\Http\Requests\V1\Auth;

use App\Models\ReferralCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @OA\Schema(
     *     schema="RegisterRequest",
     *     required={"first_name", "last_name", "email", "captcha", "key", "password"},
     *
     *     @OA\Property(property="email", type="string", format="email", description="The user's email address."),
     *     @OA\Property(property="password", type="string", format="password", minLength=8, description="The user's password."),
     *     @OA\Property(property="introducer_code", type="string", nullable=true, description="Referral code, if applicable."),
     *     @OA\Property(property="key", type="string", description="Captcha's key required in production"),
     *     @OA\Property(property="captcha", type="string", description="required in production."),
     * )
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->whereNotNull('email_verified_at')],
            'password' => ['required', 'string', 'min:8'],
            'introducer_code' => ['nullable', 'string', Rule::exists(ReferralCode::class, 'code')],
            'captcha' => ['required', 'array'],
            'captcha.key' => App::isProduction() ? ['required', 'string'] : ['nullable'],
            'captcha.value' => App::isProduction() ? ['required', 'captcha_api:'.request('captcha.key').',flat'] : ['nullable'],
        ];
    }
}
