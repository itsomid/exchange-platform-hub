<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\User\NotificationCollection;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(10); // Fetch paginated notifications

        return new NotificationCollection($notifications);
    }

    public function unread()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->whereNull('read_at')->paginate(10); // Fetch paginated notifications

        return new NotificationCollection($notifications);
    }


    public function countUnread()
    {
        $user = Auth::user();
        $notificationsCount = $user->notifications()->whereNull('read_at')->count();

        return response([
            'data' => [
                'count_unread' => $notificationsCount,
            ],
        ]);
    }


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


    public function markAsReadAll()
    {
        $user = Auth::user();
        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => __('notification.mark-as-read')]);
    }
}
