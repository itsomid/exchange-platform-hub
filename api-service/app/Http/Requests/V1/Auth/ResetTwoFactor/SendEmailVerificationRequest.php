<?php

namespace App\Http\Requests\V1\Auth\ResetTwoFactor;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="SendEmailVerificationRequest",
 *     required={"email"},
 *
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="The email address of the user requesting to disable two-factor authentication.",
 *         example="mehdints@gmail.com"
 *     ),
 * )
 */
class SendEmailVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
