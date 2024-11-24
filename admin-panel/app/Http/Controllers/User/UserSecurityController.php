<?php

namespace App\Http\Controllers\User;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserSecurityController extends Controller
{
    public function index(User $user)
    {
        return view('dashboard.user.security',['user'=>$user]);
    }

    public function sendResetLinkEmail()
    {
        //TODO: reset password email
        Toast::message('لینک بازیابی رمز عبور با موفقیت به کاربر ارسال شد')->success()->notify();

        return redirect()->back();
    }
    public function passwordEdit(User $user)
    {

        return view('dashboard.user.edit-password',['user'=>$user]);
    }
    public function passwordUpdate(Request $request, User $user)
    {
        $this->validate($request, [
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user->password = Hash::make($request->password);
        $user->save();
        Toast::message('رمز عبور شما با موفقیت تغییر کرد.')->success()->notify();

        return redirect()->back();
    }
}
