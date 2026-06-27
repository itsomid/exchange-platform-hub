<?php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ReplyTicketRequest",
 *     required={"message"},
 *
 *     @OA\Property(property="message", type="string", example="We are looking into your issue."),
 *     @OA\Property(property="is_private", type="boolean", example=false),
 *    @OA\Property(property="image", type="string", nullable=true, example="file_content")
 * )
 */
class ReplyTicketRequest extends FormRequest
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
            'message' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('message')) {
            $this->merge(['message' => strip_tags($this->message)]);
        }
    }
}
