<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\NotificationRecord;
use Illuminate\Http\Request;


class AdminNotificationController extends Controller
{
    public function index()
    {
        $admin = auth()->user(); // Get authenticated admin

        if ($admin->hasPermissionTo('all_notifications')) {
            $notifications = NotificationRecord::where('notifiable_type','App\Models\Admin')->filterBy(request()->all())->latest()->paginate(100);
        } else {
            $notifications = NotificationRecord::where('notifiable_id',$admin->id)->where('notifiable_type','App\Models\Admin')->filterBy(request()->all())->latest()->paginate(100);
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

    public function destroyAll(Request $request)
    {
        $admin = auth()->user();

        if ($admin->hasRole(['super_admin', 'admin'])) {
            $query = NotificationRecord::where('notifiable_type', 'App\Models\Admin');
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }
            // Add more filters as needed
            $query->delete();
        } else {
            $query = $admin->notifications();
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }
            // Add more filters as needed
            $query->delete();
        }

        Toast::message('تمام اعلان های فیلتر شده پاک شد.');
        return redirect()->route('admin.admin.notifications.index');
    }
}
