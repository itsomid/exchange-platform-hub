<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\User\NotificationCollection;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/notifications",
     *     summary="Get user notifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of user notifications",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="type", type="string", example="App\Notifications\OtcCreated"),
     *                     @OA\Property(property="data", type="object", example={"message": "OTC has been created successfully.", "url": "/otc"}),
     *                     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-01T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object", example={"first": "http://example.com/notifications?page=1", "last": "http://example.com/notifications?page=1", "prev": null, "next": null}),
     *             @OA\Property(property="meta", type="object", example={"current_page": 1, "from": 1, "last_page": 1, "path": "http://example.com/notifications", "per_page": 10, "to": 1, "total": 1})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(10); // Fetch paginated notifications

        return new NotificationCollection($notifications);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notifications/{id}/mark-as-read",
     *     summary="Mark a notification as read",
     *     tags={"Notifications"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Notification ID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Notification marked as read")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Notification not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();

            return response()->json(['message' => __('notification.mark-as-read')]);
        }

        return response()->json(['message' => 'Notification not found'], 404);
    }
}
