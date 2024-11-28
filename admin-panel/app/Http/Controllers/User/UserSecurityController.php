<?php

namespace App\Http\Controllers\User;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserSecurityController extends Controller
{
    public function index(User $user)
    {
        return view('dashboard.user.security',['user'=>$user]);
    }

    public function sendResetLinkEmail(User $user)
    {
        $status = Password::sendResetLink(
            ['email' => $user->email]
        );
        if($status === Password::RESET_LINK_SENT){
            Toast::message('لینک بازیابی رمز عبور با موفقیت به کاربر ارسال شد')->success()->notify();
        }else{
            report("Panel can not send reset link {$user->id}");
            Toast::message('مشکل فنی رخ داده است لطفا دقایق دیگری تلاش کنید.')->danger()->notify();
        }

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
