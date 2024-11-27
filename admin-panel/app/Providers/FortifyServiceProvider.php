<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Admin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Fortify::loginView(function () {

            $defaultUsername = App::environment('local') ? Config::get('auth.default_login.username') : '';
            $defaultPassword = App::environment('local') ? Config::get('auth.default_login.password') : '';

            return view('auth.login_cover', [
                'defaultUsername' => $defaultUsername,
                'defaultPassword' => $defaultPassword,
            ]);
        });

        Fortify::twoFactorChallengeView(function () {
            return view('auth.two-factor-challenge');
        });

        Fortify::confirmPasswordView(function () {
            return view('dashboard.profile.confirm-password.index');
        });
        Fortify::confirmPasswordsUsing(function (Admin $admin, $password) {
            if(Hash::check($password, $admin->password)){
                $enable = resolve(EnableTwoFactorAuthentication::class);
                $enable($admin, true);
                return true;
            }else{
                return false;
            }
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = Admin::query()->where('mobile', $request->email)->orWhere('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            } else {
                // custom error message here -- doesn't work
                //                throw ValidationException::withMessages([
                //                    Fortify::username() => "حساب کاربری موجود نمی باشد یا غیرفعال هست.",
                //                ]);
                $request->session()->flash('status', 'از درستی اطلاعات وارد شده مطمئن شده و دوباره امتحان کنید.');
            }
        });

        RateLimiter::for('login', function (Request $request) {
            $username = (string) $request->email;

            return Limit::perMinute(50)->by($username.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

    }
}
