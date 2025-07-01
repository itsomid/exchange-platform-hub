<?php

namespace App\Http\Requests\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
     *      schema="UpdateProfileRequest",
     *      type="object",
     *      required={"first_name", "last_name", "mobile"},
     *
     *      @OA\Property(
     *          property="first_name",
     *          type="string",
     *          description="The first name of the user.",
     *          example="John"
     *      ),
     *      @OA\Property(
     *          property="last_name",
     *          type="string",
     *          description="The last name of the user.",
     *          example="Doe"
     *      ),
     *      @OA\Property(
     *          property="mobile",
     *          type="string",
     *          description="The mobile number of the user.",
     *          example="09107588958"
     *      )
     *  )
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:30'],
            'last_name' => ['required', 'string', 'max:30'],
            'national_code' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/', 'unique:users,national_code,' . $this->user()->id],
            'mobile' => ['required', 'string', 'unique:users,mobile,' . $this->user()->id],
        ];
    }

    public function messages(): array
    {
        return [
            'national_code.unique' => 'کد ملی وارد شده اشتباه است.',
        ];
    }
}
