<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $admin = Auth::guard('admin')->user();
        return view('dashboard.profile.edit', ['admin' => $admin]);
    }

    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $this->validate($request, [
            'first_name' => ['required', 'max:30'],
            'last_name' => ['required', 'max:30'],
            'mobile' => ['required', 'max:30'],
        ]);

        $admin->update($request->only([
            'first_name', 'last_name', 'mobile', 'gender', 'email', 'instagram', 'telegram', 'whatsapp'
        ]));

        Toast::message('اطلاعات شما با موفقیت ویرایش شد.')->success()->notify();
        return redirect()->back();
    }

    public function passwordEdit()
    {
        $admin = Auth::guard('admin')->user();
        return view('dashboard.profile.edit_password', ['admin' => $admin]);
    }

    public function passwordUpdate(Request $request)
    {
        $this->validate($request, [
            'password' => ['required', 'confirmed', 'min:8']
        ]);

        $admin = Auth::guard('admin')->user();

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
