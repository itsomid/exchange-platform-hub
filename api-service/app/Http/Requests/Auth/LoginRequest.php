<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

class LoginRequest extends FormRequest
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
     *      schema="LoginRequest",
     *      required={"email", "password"},
     *
     *      @OA\Property(property="email", type="string", format="email", description="The user's email address."),
     *      @OA\Property(property="password", type="string", format="password", description="The user's password."),
     *      @OA\Property(property="captcha", type="string", nullable=true, description="Captcha response if in production."),
     *  )
     *  Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'captcha' => App::isProduction() ? ['required', 'captcha_api:'.request('key').',flat'] : ['nullable'],
        ];
    }
}
