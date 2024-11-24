<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ValidateTwoFactorRequest extends FormRequest
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
     *      schema="ValidateTwoFactorRequest",
     *      required={"google2fa"},
     *
     *      @OA\Property(property="google2fa", type="string", description="The user's two-factor authentication code.")
     *  )
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'google2fa' => ['required', 'string', function ($attribute, $value, $fail) {
                $user = Auth::user();
                if ($user && empty($user->google2fa_secret)) {
                    $fail(__('auth.login.2fa-required'));
                }
            }],
        ];
    }
}
