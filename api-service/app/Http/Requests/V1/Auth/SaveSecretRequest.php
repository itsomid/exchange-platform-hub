<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SaveSecretRequest extends FormRequest
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
     *      schema="Save2FASecretRequest",
     *      type="object",
     *      title="Save 2FA Secret Request",
     *      description="Request body for saving the 2FA secret.",
     *      required={"2fa", "secret"},
     *
     *      @OA\Property(
     *          property="2fa",
     *          type="string",
     *          description="The 2FA token entered by the user.",
     *          example="123456"
     *      ),
     *      @OA\Property(
     *          property="secret",
     *          type="string",
     *          description="The secret key used for generating 2FA tokens.",
     *          example="JBSWY3DPEHPK3PXP"
     *      )
     *  )
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '2fa' => ['required'],
            'secret' => ['required', function ($attribute, $value, $fail) {
                if (!empty(Auth::user()->two_factor_secret)){
                    $fail('You have already set up 2FA.');
                }
            }],
        ];
    }
}
