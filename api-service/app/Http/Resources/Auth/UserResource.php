<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $first_name
 * @property string $last_name
 * @property string $national_code
 * @property string $mobile
 * @property string $email
 * @property string $username
 */
class UserResource extends JsonResource
{
    public function __construct($resource, private readonly ?string $encryptedToken = null)
    {
        $this->resource = $resource;
        parent::__construct($this->resource);
    }

    /**
     * @OA\Schema(
     *      schema="UserResource",
     *
     *      @OA\Property(property="first_name", type="string", description="The user's first name."),
     *      @OA\Property(property="last_name", type="string", description="The user's last name."),
     *      @OA\Property(property="email", type="string", format="email", description="The user's email address."),
     *      @OA\Property(property="username", type="string", description="The user's username"),
     *      @OA\Property(property="has_two_factor", type="boolean", description="Indicates whether the user has two-factor authentication enabled.")
     *  )
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'national_code' => $this->national_code,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'username' => $this->username,
            'has_two_factor' => ! empty($this->two_factor_secret),
            'encrypted_token' => $this->when(! empty($this->two_factor_secret), $this->encryptedToken),
        ];
    }
}
