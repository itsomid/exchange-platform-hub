<?php

namespace App\Http\Controllers\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Profile\ActiveSessionCollection;
use App\Services\Profile\ProfileService;

class SessionController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/profile/sessions/active",
     *     summary="Get Active Sessions",
     *     description="Retrieve a list of active sessions for the authenticated user.",
     *     tags={"Profile"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active sessions retrieved successfully.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/ActiveSession")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while fetching active sessions.")
     *         )
     *     )
     * )
     */
    public function active()
    {
        return new ActiveSessionCollection(
            $this->profileService->getActiveSessions(
                auth()->id()
            )
        );
    }
}
