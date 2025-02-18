<?php

namespace App\Http\Requests\V1\User;

use App\Enums\TicketTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="StoreTicketRequest",
 *     required={"subject", "message", "priority", "ticketable_id", "ticketable_type"},
 *
 *     @OA\Property(property="subject", type="string", example="Withdrawal Issue"),
 *     @OA\Property(property="message", type="string", example="My withdrawal is stuck."),
 *     @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="high"),
 *     @OA\Property(property="ticketable_id", type="integer", example=123),
 *     @OA\Property(property="ticketable_type", type="string", example="App\Models\Withdrawal"),
 *     @OA\Property(property="image", type="string", nullable=true, example="file_content")
 * )
 */
class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ensure authentication middleware handles access
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:low,medium,high'],
            'ticketable_id' => ['integer'],
            'ticketable_type' => [Rule::enum(TicketTypeEnum::class)],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
}
