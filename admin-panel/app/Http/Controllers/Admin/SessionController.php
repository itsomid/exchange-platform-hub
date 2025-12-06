<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Session;
use Illuminate\Support\Carbon;

class SessionController extends Controller
{
    public function index(Admin $admin)
    {
        // Eager load sessions with ordering by last_activity desc
        $admin->load(['sessions' => function ($query) {
            $query->orderByDesc('last_activity');
        }]);

        // Get current session ID for highlighting
        $currentSessionId = session()->getId();

        return view('dashboard.admin.session.index')
            ->with('admin', $admin)
            ->with('currentSessionId', $currentSessionId);
    }

    public function destroy(Admin $admin, Session $session)
    {
        $session->delete();
        Toast::message('نشست با موفقیت حذف شد.')->success()->notify();

        return redirect()->route('admin.session.index', ['admin' => $admin]);
    }

    public function purge(Admin $admin)
    {
        $count = Session::whereUserId($admin->id)->count();
        Session::whereUserId($admin->id)->delete();
        Toast::message("تمام {$count} نشست فعال کاربر پاک شد.")->success()->notify();

        return redirect()->route('admin.session.index', ['admin' => $admin]);
    }

    /**
     * Remove all expired sessions for the admin
     */
    public function destroyExpired(Admin $admin)
    {
        $expirationTime = Carbon::now()->subMinutes(config('session.lifetime'))->getTimestamp();
        
        $count = Session::where('user_id', $admin->id)
            ->where('last_activity', '<', $expirationTime)
            ->count();
            
        Session::where('user_id', $admin->id)
            ->where('last_activity', '<', $expirationTime)
            ->delete();
            
        if ($count > 0) {
            Toast::message("{$count} نشست منقضی شده پاک شد.")->success()->notify();
        } else {
            Toast::message('نشست منقضی شده‌ای یافت نشد.')->info()->notify();
        }

        return redirect()->route('admin.session.index', ['admin' => $admin]);
    }
}
