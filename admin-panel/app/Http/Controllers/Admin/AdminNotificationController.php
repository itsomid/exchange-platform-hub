<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $admin = auth()->user(); // Get authenticated admin


        if ($admin->hasRole(['super_admin', 'admin'])) {
            $notifications = DatabaseNotification::where('notifiable_type','App\Models\Admin')->latest()->paginate(100);
        } else {
            $notifications = $admin->notifications()->latest()->paginate(100);
        }

        return view('dashboard.admin.notification.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($id)
    {
        $admin = auth()->user();
        $notification = $admin->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read');
    }

    public function markAllAsRead()
    {
        $admin = auth()->user();
        $admin->unreadNotifications->markAsRead();

        Toast::message('تمام اعلان ها به خوانده شده تغییر یافت.');
        return back();
    }
}
