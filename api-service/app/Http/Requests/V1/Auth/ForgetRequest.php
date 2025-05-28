<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

class ForgetRequest extends FormRequest
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
     *      schema="ForgotPasswordRequest",
     *      required={"email"},
     *
     *      @OA\Property(property="email", type="string", description="The user's email address where the password reset link will be sent.")
     *  )
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'captcha' => App::isProduction() ? ['required', 'array'] : ['nullable'],
            'captcha.key' => App::isProduction() ? ['required', 'string'] : ['nullable'],
            'captcha.value' => App::isProduction() ? ['required', 'captcha_api:'.request('captcha.key').',flat'] : ['nullable'],
        ];
    }
}
