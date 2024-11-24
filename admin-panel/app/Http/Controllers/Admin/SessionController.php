<?php

namespace App\Http\Controllers\Admin;

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
}
