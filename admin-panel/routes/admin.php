<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSecurityController;
use App\Http\Controllers\Admin\ProfileController;

use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\ReferralCodeController;
use App\Http\Controllers\Admin\SelectsApiController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\RolePermission\PermissionController;
use App\Http\Controllers\RolePermission\RoleController;
use App\Http\Controllers\Setting\ExternalSettingController;
use App\Http\Controllers\Setting\InternalSettingController;
use App\Http\Controllers\Setting\ThemeController;
use App\Http\Controllers\User\InquiryController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserSecurityController;
use App\Http\Controllers\Exchange\CurrencyController;
use App\Http\Controllers\User\UserFinancialBlockController;
use App\Http\Controllers\Exchange\CurrencyChainController;
use App\Http\Controllers\Exchange\MarketController;

Route::get('/users_select', [SelectsApiController::class, 'users'])->name('users.select.index');
Route::get('/admins_select', [SelectsApiController::class, 'admins'])->name('admins.select.index');

Route::post('/set-theme', [ThemeController::class, 'setTheme'])->name('set-theme');
Route::get('/', [HomeController::class, 'index'])->name('dashboard');

//*********ADMIN*********//
Route::prefix('admins')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index')->can('admin.index');
    Route::get('/create', [AdminController::class, 'create'])->name('admin.create')->can('admin.create');
    Route::post('/', [AdminController::class, 'store'])->name('admin.store')->can('admin.create');
    Route::get('/{admin}/edit', [AdminController::class, 'edit'])->name('admin.edit')->can('admin.edit');
    Route::patch('/{admin}', [AdminController::class, 'update'])->name('admin.update')->can('admin.edit');
    Route::get('/{admin}/toggle', [AdminController::class, 'toggle'])->name('admin.toggle')->can('admin.toggle');
    Route::get('/{admin}/update-password', [AdminSecurityController::class, 'passwordEdit'])->name('admin.password.edit')->can('admin.edit');
    Route::patch('/{admin}/update-password', [AdminSecurityController::class, 'passwordUpdate'])->name('admin.password.update')->can('admin.edit');
    Route::patch('/{admin}/2fa', [AdminSecurityController::class, 'twoFAEdit'])->name('admin.2fa.edit')->can('admin.edit');

    Route::get('/{admin}/login_as_admin', [AdminController::class, 'login_as_admin'])->name('admin.login_as_admin')->can('admin.login-as-admin');
    Route::get('/back_to_admin_panel', [AdminController::class, 'back_to_admin_panel'])->name('admin.back_to_admin_panel');

    Route::get('/{admin}/session', [SessionController::class, 'index'])->name('session.index')->can('session.index');
    Route::delete('/{admin}/session/{session}', [SessionController::class, 'destroy'])->name('session.destroy')->can('session.destroy');
    Route::delete('/{admin}/session/purge', [SessionController::class, 'purge'])->name('session.purge')->can('session.destroy');
});


Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::get('/profile/password', [ProfileController::class, 'passwordEdit'])->name('profile.password.edit');
Route::patch('/profile/update-password', [ProfileController::class, 'passwordUpdate'])->name('profile.password.update');
Route::get('/profile/2fa', [ProfileController::class, 'twoFAEdit'])->name('profile.2fa.edit');

Route::get('/users', [UserController::class, 'index'])->name('user.index')->can('user.index');
Route::get('/users/create', [UserController::class, 'create'])->name('user.create')->can('user.create');
Route::post('/users', [UserController::class, 'store'])->name('user.store')->can('user.create');
Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('user.edit')->can('user.edit');
Route::patch('/users/{user}/update', [UserController::class, 'update'])->name('user.update')->can('user.edit');
Route::get('/users/{user}/update-password', [UserSecurityController::class, 'passwordEdit'])->name('user.password.edit');
Route::patch('/users/{user}/update-password', [UserSecurityController::class, 'passwordUpdate'])->name('user.password.update');
Route::get('/users/{user}/security', [UserSecurityController::class, 'index'])->name('user.security');
Route::get('/users/{user}/reset-password', [UserSecurityController::class, 'sendResetLinkEmail'])->name('user.reset-password-email');
Route::get('/users/{user}/financial-status', [UserFinancialBlockController::class, 'getBlocks'])->name('user.financial-block.getBlocks');
Route::post('/users/{user}/financial-status', [UserFinancialBlockController::class, 'addBlock'])->name('user.financial-block.addBlock');


//Route::get('/users/2fa', [UserSecurityController::class, ''])->name('profile.2fa.edit');
//Route::get('/users/{user}/tokens', [UserTokenController::class, 'twoFAEdit'])->name('user.token.index')->can('user.edit');
//Route::patch('/users/{user}/tokens/{token}/revoke', [UserTokenController::class, 'revoke'])->name('user.token.revoke')->can('user.edit');

Route::get('/role/{admin}', [AdminRoleController::class, 'edit'])->name('role.user.edit')->can('role.admin.edit');
Route::patch('/role/{admin}', [AdminRoleController::class, 'update'])->name('role.user.update')->can('role.admin.edit');
Route::get('/roles', [RoleController::class, 'index'])->name('role.index')->can('role.index');
Route::get('/roles/create', [RoleController::class, 'create'])->name('role.create')->can('role.create');
Route::post('/roles', [RoleController::class, 'store'])->name('role.store')->can('role.create');
Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('role.edit')->can('role.edit');
Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('role.update')->can('role.edit');

Route::get('/permissions', [PermissionController::class, 'index'])->name('permission.index')->can('permission.index');
Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permission.edit')->can('permission.edit');
Route::patch('/permissions/{permission}', [PermissionController::class, 'update'])->name('permission.update')->can('permission.edit');

Route::get('/referral-codes', [ReferralCodeController::class, 'index'])->name('referral_code.index')->can('referral_code.index');
Route::get('/referral-codes/create', [ReferralCodeController::class, 'create'])->name('referral_code.create')->can('referral_code.create');
Route::post('/referral-codes', [ReferralCodeController::class, 'store'])->name('referral_code.store')->can('referral_code.create');
Route::get('/referral-codes/{referral_code}/edit', [ReferralCodeController::class, 'edit'])->name('referral_code.edit')->can('referral_code.edit');
Route::patch('/referral-codes/{referral_code}', [ReferralCodeController::class, 'update'])->name('referral_code.update')->can('referral_code.edit');

Route::get('/inquiry', [InquiryController::class, 'index'])->name('inquiry.index')->can('admin.inquiry');
Route::post('/inquiry', [InquiryController::class, 'submit'])->name('inquiry.submit')->can('admin.inquiry');

Route::get('/internal-settings', [InternalSettingController::class, 'index'])->name('internal.setting.index')->can('setting.int.index');
Route::post('/internal-settings/update-permissions', [InternalSettingController::class, 'updatePermissions'])->name('setting.int.update-permissions')->can('setting.int.index');

Route::get('/external-settings', [ExternalSettingController::class, 'index'])->name('external-setting.index')->can('setting.ext.index');
Route::post('/external-settings/update-ref-address', [ExternalSettingController::class, 'updateRefAddress'])->name('setting.ext.update-ref-address')->can('setting.ext.index');

Route::get('/exchange/currencies', [CurrencyController::class, 'index'])->name('currency.index')->can('currency');
Route::get('/exchange/currencies/create', [CurrencyController::class, 'create'])->name('currency.create')->can('currency');
Route::post('/exchange/currencies', [CurrencyController::class, 'store'])->name('currency.store')->can('currency');
Route::get('/exchange/currencies/{currency}', [CurrencyController::class, 'show'])->name('currency.show')->can('currency');
Route::get('/exchange/currencies/{currency}/edit', [CurrencyController::class, 'edit'])->name('currency.edit')->can('currency');
Route::patch('/exchange/currencies/{currency}', [CurrencyController::class, 'update'])->name('currency.update')->can('currency');
Route::delete('/exchange/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currency.destroy')->can('currency');

Route::get('/exchange/currencies/{currency}/chains', [CurrencyChainController::class, 'getChains'])->name('currency.chains.edit')->can('currency');
Route::get('/exchange/currencies/{currency}/chains/create', [CurrencyChainController::class, 'createChain'])->name('currency.chains.create')->can('currency');
Route::post('/exchange/currencies/{currency}/chains', [CurrencyChainController::class, 'storeChain'])->name('currency.chains.store')->can('currency');
Route::patch('/exchange/currencies/{currency}/chains', [CurrencyChainController::class, 'updateChains'])->name('currency.chains.update')->can('currency');

Route::get('/exchange/markets',[MarketController::class,'index'])->name('market.index')->can('market');
Route::get('/exchange/markets/create',[MarketController::class,'create'])->name('market.create')->can('market');
Route::post('/exchange/markets',[MarketController::class,'store'])->name('market.store')->can('market');
Route::get('/exchange/markets/{market}/edit',[MarketController::class,'edit'])->name('market.edit')->can('market');
Route::patch('/exchange/markets/{market}',[MarketController::class,'update'])->name('market.update')->can('market');
