<?php

namespace App\Http\Resources\V1\Profile;

use App\Services\Profile\DTO\UserActiveSessionsResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ActiveSessionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn (UserActiveSessionsResponseDTO $session) => [
            'ip' => $session->getIp(),
            'location' => $session->getLocation(),
            'platform' => $session->getPlatform(),
            'browser' => $session->getBrowser(),
            'login_at' => $session->getLoginAt(),
        ])->toArray();
    }
}
