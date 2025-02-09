<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TicketReplyResource",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="message", type="string", example="We are looking into your issue."),
 *     @OA\Property(property="is_seen", type="boolean", example=true),
 *     @OA\Property(property="replied_by", type="object",
 *        @OA\Property(property="id", type="integer", example="1"),
 *        @OA\Property(property="name", type="string", example="mehdi"),
 *        @OA\Property(property="type", type="string", enum={"admin", "user"}, example="admin")
 *      ),
 *     @OA\Property(property="image", type="string", nullable=true, example="https://yourdomain.com/storage/tickets/image1.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 */
class TicketReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'is_seen' => (bool) $this->is_seen,
            'replied_by' => [
                'id' => $this->repliable->id,
                'name' => $this->repliable->name,
                'type' => class_basename($this->repliable), // User or Admin
            ],
            'image' => $this->getImageUrl(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
