<?php

namespace App\Http\Controllers\User;

use App\Enums\FinancialBlockReasonsEnum;
use App\Enums\UserFinancialBlockAction;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFinancialBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Jenssegers\Agent\Agent;

class UserSecurityController extends Controller
{
    public function index(User $user)
    {
        $agent = new Agent();
        $tokens = $user->tokens()->orderBy('created_at','DESC')->paginate(10);
        return view('dashboard.user.security', [
            'user' => $user,
            'agent' => $agent,
            'tokens' => $tokens,
        ]);
    }

    public function sendResetLinkEmail(User $user)
    {
        $status = Password::sendResetLink(
            ['email' => $user->email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            Toast::message('لینک بازیابی رمز عبور با موفقیت به کاربر ارسال شد')->success()->notify();
        }elseif ($status === Password::RESET_THROTTLED){
            Toast::message('تعداد درخواست از حد مجاز بیشتر شده است. دقایقی بعد تلاش کنید.')->danger()->notify();
        } else {
            report("Panel can not send reset link {$user->id}");
            Toast::message('مشکل فنی رخ داده است لطفا دقایق دیگری تلاش کنید.')->danger()->notify();
        }

        return redirect()->back();
    }

    public function disableTwoFactor(User $user)
    {
        $user->update([
            'two_factor_secret' => null,
        ]);

        UserFinancialBlock::query()->create([
            'user_id' => $user->id,
            'action' => UserFinancialBlockAction::WITHDRAW,
            'reason' => FinancialBlockReasonsEnum::DISABLE_TWO_FACTOR->value,
            'admin_id' => \Auth::guard('admin')->user()->id,
            'description' => 'غیر فعالسازی دومرحله ای',
            'restricted_until' => now()->addDays(2),
        ]);

        Toast::message('ورود دومرحله ایی کاربر با موفقیت غیر فعال شد.')->success()->notify();

        return redirect()->back();
    }

    public function passwordEdit(User $user)
    {

        return view('dashboard.user.edit-password', ['user' => $user]);
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
