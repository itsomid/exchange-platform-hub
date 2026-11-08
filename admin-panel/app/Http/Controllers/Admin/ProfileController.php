<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;

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
        return view('dashboard.profile.edit_password', [
            'admin' => $admin,
            'passwordRequirements' => StrongPassword::requirements(),
            'forbiddenTokens' => StrongPassword::forbiddenTokens($admin),
        ]);
    }

    public function passwordUpdate(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $this->validate($request, [
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', 'different:current_password', new StrongPassword($admin)],
        ], [
            'current_password.required' => 'گذرواژه فعلی را وارد کنید.',
            'current_password.current_password' => 'گذرواژه فعلی اشتباه است.',
            'password.required' => 'گذرواژه جدید را وارد کنید.',
            'password.confirmed' => 'تکرار گذرواژه با گذرواژه جدید یکسان نیست.',
            'password.different' => 'گذرواژه جدید باید با گذرواژه فعلی متفاوت باشد.',
        ]);

        $admin->changePassword($request->password);

        Toast::message('رمز عبور شما با موفقیت تغییر کرد.')->success()->notify();
        return redirect()->back();
    }

    public function twoFAEdit(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $data = [
            'admin' => $admin,
            'state' => $admin->twoFAStatus() ? 'enabled' : ($admin->twoFAPending() ? 'pending' : 'disabled'),
            'flash' => $request->query('flash'),
            'qrCodeSvg' => null,
            'otpauthUrl' => null,
            'secretKey' => null,
        ];

        // The QR code / secret are only exposed while setup is pending and the password was re-confirmed recently.
        // Once 2FA is confirmed they are never rendered again.
        if ($data['state'] === 'pending' && $this->passwordRecentlyConfirmed($request)) {
            $data['qrCodeSvg'] = $admin->twoFactorQrCodeSvg();
            $data['otpauthUrl'] = $admin->twoFactorQrCodeUrl();
            $data['secretKey'] = Fortify::currentEncrypter()->decrypt($admin->two_factor_secret);
        }

        return view('dashboard.profile.edit_2fa', $data);
    }

    private function passwordRecentlyConfirmed(Request $request): bool
    {
        return (time() - $request->session()->get('auth.password_confirmed_at', 0)) < config('auth.password_timeout', 10800);
    }

}
