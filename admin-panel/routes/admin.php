<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSecurityController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReferralCodeController;
use App\Http\Controllers\Admin\SelectsApiController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Exchange\CurrencyChainController;
use App\Http\Controllers\Exchange\CurrencyController;
use App\Http\Controllers\Exchange\MarketController;
use App\Http\Controllers\Exchange\NodeProviderController;
use App\Http\Controllers\Exchange\RefExchangeController;
use App\Http\Controllers\RolePermission\PermissionController;
use App\Http\Controllers\RolePermission\RoleController;
use App\Http\Controllers\Setting\ExternalSettingController;
use App\Http\Controllers\Setting\InternalSettingController;
use App\Http\Controllers\Setting\ThemeController;
use App\Http\Controllers\User\InquiryController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserFinancialBlockController;
use App\Http\Controllers\User\UserSecurityController;
use App\Http\Controllers\User\UserWalletController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Controllers\OTCOrder\OTCOrderController;
use App\Http\Controllers\Deposit\DepositController;
use App\Http\Controllers\Withdrawal\WithdrawalController;
use App\Http\Controllers\Exchange\ExchangeWalletController;
use App\Http\Controllers\Withdrawal\WithdrawalReportController;
use App\Http\Controllers\Deposit\DepositReportController;
use Illuminate\Support\Facades\Route;


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

    Route::delete('/{admin}/sessions/{session}', [SessionController::class, 'destroy'])->name('session.destroy')->can('session.destroy');
    Route::delete('/{admin}/sessions/purge/all', [SessionController::class, 'purge'])->name('session.purge')->can('session.destroy');
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


Route::get('/users/financial-status',[UserFinancialBlockController::class, 'index'])->name('user.financial-status');
Route::get('/users/{user}/financial-status', [UserFinancialBlockController::class, 'getBlocks'])->name('user.financial-block.getBlocks');
Route::post('/users/{user}/financial-status', [UserFinancialBlockController::class, 'addBlock'])->name('user.financial-block.addBlock');
Route::delete('/users/{user}/financial-status/{financialBlock}', [UserFinancialBlockController::class, 'removeBlock'])->name('user.financial-block.deleteBlock');

Route::get('/users/financial-status/mass-block', [UserFinancialBlockController::class, 'createMassBlock'])->name('user.financial-block.create-mass-block');
Route::post('/users/financial-status/mass-block', [UserFinancialBlockController::class, 'storeMassBlock'])->name('user.financial-block.store-mass-block');

Route::get('/users/{user}/wallets',[UserWalletController::class,'userWallets'])->name('wallet.index')->can('wallet');
Route::get('/users/{user}/wallets/{wallet}/{type}',[UserWalletController::class,'walletDetails'])->name('wallet.detail')->can('wallet');
Route::get('/users/{user}/inquiry',[InquiryController::class,'userDetails'])->name('inquiry.user-details')->can('admin.inquiry');

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
Route::get('/referral-codes/{referral_code}', [ReferralCodeController::class, 'showUsage'])->name('referral_code.show')->can('referral_code.index');
Route::get('/referral-codes/referred-user/{user}/transactions', [ReferralCodeController::class, 'showTransactionsForReferredUser'])->name('referral_code.showTransactionsForReferredUser')->can('referral_code.index');
Route::post('/referral-codes', [ReferralCodeController::class, 'store'])->name('referral_code.store')->can('referral_code.create');
Route::get('/referral-codes/{referral_code}/edit', [ReferralCodeController::class, 'edit'])->name('referral_code.edit')->can('referral_code.edit');
Route::patch('/referral-codes/{referral_code}', [ReferralCodeController::class, 'update'])->name('referral_code.update')->can('referral_code.edit');

Route::get('/inquiry', [InquiryController::class, 'index'])->name('inquiry.index')->can('admin.inquiry');
Route::post('/inquiry', [InquiryController::class, 'submit'])->name('inquiry.submit')->can('admin.inquiry');

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

Route::get('/exchange/currencies/{currency}/nodeprovider', [NodeProviderController::class, 'edit'])->name('currency.nodeprovider.edit')->can('currency');
Route::patch('/exchange/currencies/{currency}/nodeprovider', [NodeProviderController::class, 'update'])->name('currency.nodeprovider.update')->can('currency');

Route::get('/exchange/markets',[MarketController::class,'index'])->name('market.index')->can('market');
Route::get('/exchange/markets/create',[MarketController::class,'create'])->name('market.create')->can('market');
Route::post('/exchange/markets',[MarketController::class,'store'])->name('market.store')->can('market');
Route::get('/exchange/markets/{market}/edit',[MarketController::class,'edit'])->name('market.edit')->can('market');
Route::patch('/exchange/markets/{market}',[MarketController::class,'update'])->name('market.update')->can('market');
Route::get('/exchange/ref-exchanges',[RefExchangeController::class,'index'])->name('exchange.index')->can('ref-exchanges');
Route::get('/exchange/wallets',[ExchangeWalletController::class,'index'])->name('exchange.wallet');


Route::prefix('transactions')->group(function (){
   Route::get('/',[TransactionController::class,'index'])->name('transaction.index')->can('transaction');
});

Route::prefix('otc_orders')->group(function (){
    Route::get('/',[OTCOrderController::class,'index'])->name('otc_orders.index')->can('transaction');
});

Route::prefix('deposits')->group(function (){
   Route::get('/',[DepositController::class,'index'])->name('deposit.index')->can('deposit');
});

Route::prefix('withdrawal')->group(function (){
    Route::get('/',[WithdrawalController::class,'index'])->name('withdrawal.index')->can('withdrawal');
    Route::get('/{withdraw}/confirm',[WithdrawalController::class,'confirmWithdrawal'])->name('withdrawal.confirm-withdrawal')->can('withdrawal');
    Route::get('/{withdraw}/cancel',[WithdrawalController::class,'cancelWithdrawal'])->name('withdrawal.cancel-withdrawal')->can('withdrawal');
});

Route::get('/internal-settings', [InternalSettingController::class, 'index'])->name('internal.setting.index')->can('setting.int.index');
Route::post('/internal-settings/update-permissions', [InternalSettingController::class, 'updatePermissions'])->name('setting.int.update-permissions')->can('setting.int.index');
Route::post('/internal-settings/update-otc-setting', [InternalSettingController::class, 'updateOTCSetting'])->name('setting.int.update-otc-setting')->can('setting.int.index');
Route::post('/internal-settings/update-referral-setting', [InternalSettingController::class, 'updateReferralSetting'])->name('setting.int.update-referral-setting')->can('setting.int.index');

Route::get('/external-settings', [ExternalSettingController::class, 'index'])->name('external-setting.index')->can('setting.ext.index');
Route::post('/external-settings/update-ref-address', [ExternalSettingController::class, 'updateRefAddress'])->name('setting.ext.update-ref-address')->can('setting.ext.index');

Route::prefix('wallet')->group(function (){
    Route::get('increase-credit',[WalletController::class,'increaseCreditForm'])->name('wallet.increase-credit.form')->can('wallet');
    Route::post('increase-credit',[WalletController::class,'increaseCredit'])->name('wallet.increase-credit')->can('wallet');
    Route::get('{wallet}/user/{user}/block-balance', [WalletController::class, 'blockBalanceForm'])->name('wallet.block-balance.form')->can('wallet');
    Route::post('{wallet}/block-balance', [WalletController::class, 'blockBalance'])->name('wallet.block-balance')->can('wallet');
    Route::get('{wallet}/user/{user}/unblock-balance', [WalletController::class, 'unblockBalanceForm'])->name('wallet.unblock-balance.form')->can('wallet');
    Route::post('{wallet}/unblock-balance', [WalletController::class, 'unblockBalance'])->name('wallet.unblock-balance')->can('wallet');
});

Route::prefix('report')->group(function (){
    Route::get('deposit',[DepositReportController::class,'index'])->name('report.deposit')->can('report');
    Route::get('withdrawal',[WithdrawalReportController::class,'index'])->name('report.withdrawal')->can('report');

});
