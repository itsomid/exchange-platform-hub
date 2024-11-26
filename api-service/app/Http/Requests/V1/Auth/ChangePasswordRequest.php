<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
     *      schema="ResetPasswordRequest",
     *      required={"token", "email", "password"},
     *
     *      @OA\Property(property="token", type="string", description="The password reset token sent to the user's email."),
     *      @OA\Property(property="email", type="string", description="The email address associated with the account."),
     *      @OA\Property(property="password", type="string", description="The new password to set for the user account.")
     *  )
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ];
    }
}
