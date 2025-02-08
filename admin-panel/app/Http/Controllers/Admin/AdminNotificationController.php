<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $admin = auth()->user(); // Get authenticated admin
         $notifications = $admin->notifications;

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
}
