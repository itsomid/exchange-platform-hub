<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TicketResource",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="ticket_number", type="string", example="TCK-12345ABC"),
 *     @OA\Property(property="subject", type="string", example="Withdrawal Issue"),
 *     @OA\Property(property="message", type="string", example="My withdrawal is stuck."),
 *     @OA\Property(property="status", type="string", example="open"),
 *     @OA\Property(property="priority", type="string", example="high"),
 *     @OA\Property(property="ticket_type", type="string", example="withdrawal"),
 *     @OA\Property(property="ticketable_id", type="string", example="1"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *          @OA\Property(
 *          property="replies",
 *          type="array",
 *
 *          @OA\Items(ref="#/components/schemas/TicketReplyResource")
 *      )
 * )
 */
class TicketResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'status' => $this->status,
            'priority' => $this->priority,
            'ticket_type' => $this->getTicketType(), // ✅ Normalized type
            'ticketable_id' => $this->ticketable_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),
        ];
    }

    /**
     * Get a normalized ticket type.
     */
    private function getTicketType(): string
    {
        return strtolower(class_basename($this->ticketable_type));
    }
}
