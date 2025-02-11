<?php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;

class ReferralCodeCreateRequest extends FormRequest
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
     *      schema="ReferralCodeCreateRequest",
     *      type="object",
     *      required={"friend_fee"},
     *
     *      @OA\Property(property="friend_fee", type="integer", minimum=1, maximum=100, example=10, description="The fee the friend will pay."),
     *  )
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'friend_fee' => ['required', 'integer', 'min:0', 'max:'.config('user.referral-code.max-fee')],
        ];
    }
}
