<?php

namespace App\Http\Requests\V1\Auth\ResetTwoFactor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="DisableRequest",
 *     required={"email", "token"},
 *
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="The email address of the user requesting to disable two-factor authentication.",
 *         example="o.shabani@hotmail.com"
 *     ),
 *          @OA\Property(
 *          property="token",
 *          type="string",
 *          description="The token sent to the user's email address for verification.",
 *          example="encrypted-token-here"
 *      ),
 * )
 */
class DisableRequest extends FormRequest
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
            'token' => ['required'],
            'email' => ['required', 'email'],
        ];
    }
}
