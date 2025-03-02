<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketReplyRequest extends FormRequest
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
            'message' => 'required|string',
            'is_private' => 'boolean',
            'status'=> 'required|in:' . implode(',', TicketStatusEnum::values()),
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages()
    {
        return[
            'status.required' => 'انتخاب وضعیت الزامی است'
        ];

    }
}
