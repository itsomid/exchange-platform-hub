<?php

namespace App\Http\Requests\V1\Profile;

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
     *      schema="ChangePasswordRequest",
     *      type="object",
     *      required={"old_password", "new_password"},
     *
     *      @OA\Property(
     *          property="old_password",
     *          type="string",
     *          description="The current password of the user.",
     *          example="oldPassword123"
     *      ),
     *      @OA\Property(
     *          property="new_password",
     *          type="string",
     *          description="The new password for the user.",
     *          example="newPassword123"
     *      )
     *  )
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'old_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8'],
        ];
    }
}
