<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $name
 * @property string $email
 */
class UserResource extends JsonResource
{
    public function __construct($resource, private readonly ?string $encryptedToken)
    {
        $this->resource = $resource;
        parent::__construct($this->resource);
    }

    /**
     * @OA\Schema(
     *      schema="UserResource",
     *
     *      @OA\Property(property="name", type="string", description="The user's full name."),
     *      @OA\Property(property="email", type="string", format="email", description="The user's email address."),
     *      @OA\Property(property="has_two_factor", type="boolean", description="Indicates whether the user has two-factor authentication enabled.")
     *  )
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'has_two_factor' => ! empty($this->two_factor_secret),
            'encrypted_token' => $this->when(! empty($this->two_factor_secret), $this->encryptedToken),
        ];
    }
}
