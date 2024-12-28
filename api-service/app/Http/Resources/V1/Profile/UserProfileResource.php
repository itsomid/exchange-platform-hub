<?php

namespace App\Http\Resources\V1\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $username
 */
class UserProfileResource extends JsonResource
{
    /**
     * @OA\Schema(
     *      schema="UserProfileResource",
     *
     *      @OA\Property(property="first_name", type="string", description="The user's first name."),
     *      @OA\Property(property="last_name", type="string", description="The user's last name."),
     *      @OA\Property(property="email", type="string", format="email", description="The user's email address."),
     *      @OA\Property(property="username", type="string", description="The user's username"),
     *  )
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'username' => $this->username,
        ];
    }
}
