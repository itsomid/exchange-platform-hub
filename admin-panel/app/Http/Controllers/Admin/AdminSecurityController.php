<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminSecurityController extends Controller
{
    public function passwordEdit(Admin $admin)
    {

        return view('dashboard.admin.edit-password', ['admin' => $admin]);
    }

    public function passwordUpdate(Admin $admin, Request $request)
    {
        $this->validate($request, [
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $admin->password = Hash::make($request->password);
        $admin->save();
        Toast::message('رمز عبور شما با موفقیت تغییر کرد.')->success()->notify();

        return redirect()->back();
    }

    public function twoFAEdit()
    {
        return view('dashboard.profile.edit_2fa');
    }
}
