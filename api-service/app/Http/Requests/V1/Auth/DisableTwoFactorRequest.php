<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="DisableTwoFactorRequest",
 *     title="DisableTwoFactorRequest",
 *     description="Disable Two-Factor Authentication Request",
 *     required={"2fa"},
 *
 *     @OA\Property(
 *     property="2fa",
 *     type="integer",
 *     description="The 6-digit two-factor authentication code.",
 *     example=123456
 *     )
 * )
 */
class DisableTwoFactorRequest extends FormRequest
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
            '2fa' => ['required', 'numeric', 'min_digits:6', 'max_digits:6'],
        ];
    }
}
