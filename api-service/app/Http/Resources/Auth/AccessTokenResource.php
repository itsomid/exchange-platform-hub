<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @method getToken()
 */
class AccessTokenResource extends JsonResource
{
    /**
     * @OA\Schema(
     *      schema="AccessTokenResource",
     *
     *      @OA\Property(property="access_token", type="string", description="The generated access token.")
     *  )
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->getToken(),
        ];
    }
}
