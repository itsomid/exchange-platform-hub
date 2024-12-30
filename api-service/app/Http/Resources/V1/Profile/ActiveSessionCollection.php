<?php

namespace App\Http\Resources\V1\Profile;

use App\Services\Profile\DTO\UserActiveSessionsResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="ActiveSession",
 *     type="object",
 *     title="Active Session",
 *     description="Details of a user's active session.",
 *
 *     @OA\Property(
 *         property="ip",
 *         type="string",
 *         description="IP address of the session.",
 *         example="192.168.1.1"
 *     ),
 *     @OA\Property(
 *         property="location",
 *         type="string",
 *         description="Location of the session, if available.",
 *         example="New York, USA"
 *     ),
 *     @OA\Property(
 *         property="platform",
 *         type="string",
 *         description="Operating system/platform of the session.",
 *         example="Windows 10"
 *     ),
 *     @OA\Property(
 *         property="browser",
 *         type="string",
 *         description="Browser used for the session.",
 *         example="Google Chrome"
 *     ),
 *     @OA\Property(
 *         property="login_at",
 *         type="string",
 *         format="date-time",
 *         description="Timestamp of the session login.",
 *         example="2024-12-21T14:30:00Z"
 *     ),
 *     @OA\Property(
 *     property="is_active",
 *     type="boolean",
 *     description="Indicates if the session is still active.",
 *     example="true"
 *  )
 * )
 */
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
            'is_active' => $session->getIsActive(),
        ])->toArray();
    }
}
