<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Session;

class SessionController extends Controller
{
    public function index(Admin $admin)
    {
        return view('dashboard.admin.session.index')
            ->with('admin', $admin);
    }

    public function destroy(Admin $admin, Session $session)
    {
        $session->delete();

        return redirect()->route('admin.session.index', ['admin' => $admin]);
    }

    public function purge(Admin $admin)
    {

        Session::whereUserId($admin->id)->delete();
        Toast::message('تمام نشست های فعال کاربر پاک شد.')->success()->notify();
        return redirect()->route('admin.session.index', ['admin' => $admin]);
    }
}
