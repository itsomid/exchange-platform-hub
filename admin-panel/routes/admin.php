<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSecurityController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReferralCodeController;
use App\Http\Controllers\Admin\SelectsApiController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Deposit\DepositController;
use App\Http\Controllers\Deposit\DepositReportController;
use App\Http\Controllers\Exchange\CurrencyChainController;
use App\Http\Controllers\Exchange\CurrencyController;
use App\Http\Controllers\Exchange\ExchangeWalletController;
use App\Http\Controllers\Exchange\MarketController;
use App\Http\Controllers\Exchange\NodeProviderController;
use App\Http\Controllers\Exchange\RefExchangeController;
use App\Http\Controllers\Exchange\RefExchangeAssetsWithdrawalController;
use App\Http\Controllers\OTCOrder\OTCOrderController;
use App\Http\Controllers\SpotTrade\SpotTradeController;
use App\Http\Controllers\SpotOrder\SpotOrderController;
use App\Http\Controllers\RolePermission\PermissionController;
use App\Http\Controllers\RolePermission\RoleController;
use App\Http\Controllers\Setting\DockerController;
use App\Http\Controllers\Setting\ExternalSettingController;
use App\Http\Controllers\Setting\InternalSettingController;
use App\Http\Controllers\Setting\ThemeController;
use App\Http\Controllers\Ticket\TicketController;
use App\Http\Controllers\Ticket\TicketReplyController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\User\InquiryController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserFinancialBlockController;
use App\Http\Controllers\User\UserRegistrationReportController;
use App\Http\Controllers\User\UserSecurityController;
use App\Http\Controllers\User\UserWalletController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Controllers\Wallet\LockedBalanceDetailController;
use App\Http\Controllers\Withdrawal\WithdrawalController;
use App\Http\Controllers\Withdrawal\WithdrawalReportController;
use App\Http\Controllers\Stock\StockController;
use App\Http\Controllers\Stock\StockContractController;
use App\Http\Controllers\Setting\SpotBotSettingController;
use App\Http\Controllers\ApiSystem\ApiSystemController;
use App\Http\Controllers\ApiSystem\ApiSystemTokenController;
use App\Http\Controllers\Report\HdWalletIndexReportController;
use App\Http\Controllers\Report\HdWalletCurrencyController;

use Illuminate\Support\Facades\Route;

// Routes accessible without 2FA
Route::get('/users_select', [SelectsApiController::class, 'users'])->name('users.select.index');
Route::get('/admins_select', [SelectsApiController::class, 'admins'])->name('admins.select.index');
Route::post('/set-theme', [ThemeController::class, 'setTheme'])->name('set-theme');

// Profile and 2FA setup routes (accessible without 2FA)
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::get('/profile/password', [ProfileController::class, 'passwordEdit'])->name('profile.password.edit');
Route::patch('/profile/update-password', [ProfileController::class, 'passwordUpdate'])->name('profile.password.update');
Route::get('/profile/2fa', [ProfileController::class, 'twoFAEdit'])->name('profile.2fa.edit');

// Dashboard page accessible without 2FA
Route::get('/', [HomeController::class, 'index'])->name('dashboard');

// Dashboard AJAX endpoints
Route::prefix('dashboard/ajax')->name('dashboard.ajax.')->group(function () {
    Route::get('/kpi-stats', [HomeController::class, 'getKPIStats'])->name('kpi-stats');
    Route::get('/financial-summary', [HomeController::class, 'getFinancialSummary'])->name('financial-summary');
    Route::get('/deposit-withdraw-charts', [HomeController::class, 'getDepositWithdrawCharts'])->name('deposit-withdraw-charts');
    Route::get('/trading-stats', [HomeController::class, 'getTradingStats'])->name('trading-stats');
    Route::get('/recent-activities', [HomeController::class, 'getRecentActivities'])->name('recent-activities');
    Route::get('/top-trading-pairs', [HomeController::class, 'getTopTradingPairs'])->name('top-trading-pairs');
});

// All other admin routes require 2FA
Route::middleware(['admin.2fa'])->group(function () {
    // *********ADMIN*********//
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
        Route::delete('/{admin}/sessions/purge/expired', [SessionController::class, 'destroyExpired'])->name('session.destroy-expired')->can('session.destroy');

        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications.index')->can('notifications');
        Route::patch('/notifications/mark-read/{id}', [AdminNotificationController::class, 'markAsRead'])->name('admin.notifications.mark-read')->can('notifications');
        Route::get('/notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('admin.notifications.mark-all-read')->can('notifications');
        Route::delete('admin/notifications/destroyAll', [AdminNotificationController::class, 'destroyAll'])->name('admin.admin.notifications.destroyAll')->can('notifications');
    });

    Route::resource('/tickets', TicketController::class)->except(['ticket']);
    Route::prefix('tickets')->name('ticket.')->group(function () {
        Route::get('/{ticket}/replies', [TicketReplyController::class, 'index'])->name('replies.index');
        Route::post('/{ticket}/replies', [TicketReplyController::class, 'store'])->name('replies.store');
        Route::delete('/replies/{reply}', [TicketReplyController::class, 'destroy'])->name('replies.destroy');
    });


    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('user.index')->can('user.index');
        Route::get('/create', [UserController::class, 'create'])->name('user.create')->can('user.create');
        Route::post('/', [UserController::class, 'store'])->name('user.store')->can('user.create');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('user.edit')->can('user.edit');
        Route::patch('/{user}/update', [UserController::class, 'update'])->name('user.update')->can('user.edit');
        Route::patch('/{user}/toggle-status', [UserController::class, 'suspendUser'])->name('user.toggle-status')->can('user.index');
        Route::patch('/{user}/active-user', [UserController::class, 'activeUser'])->name('user.active-user')->can('user.index');

        Route::post('excel_export', [UserController::class, 'exportExcel'])->name('user.excel-export')->can('user.index');

        Route::get('/{user}/update-password', [UserSecurityController::class, 'passwordEdit'])->name('user.password.edit');
        Route::patch('/{user}/update-password', [UserSecurityController::class, 'passwordUpdate'])->name('user.password.update');
        Route::get('/{user}/security', [UserSecurityController::class, 'index'])->name('user.security');
        Route::get('/{user}/reset-password', [UserSecurityController::class, 'sendResetLinkEmail'])->name('user.reset-password-email');
        Route::post('/{user}/disable-user-two-factor', [UserSecurityController::class, 'disableTwoFactor'])->name('users.disable-user-two-factor');

        Route::get('/financial-status', [UserFinancialBlockController::class, 'index'])->name('user.financial-status');
        Route::get('/{user}/financial-status', [UserFinancialBlockController::class, 'getBlocks'])->name('user.financial-block.getBlocks');
        Route::post('/{user}/financial-status', [UserFinancialBlockController::class, 'addBlock'])->name('user.financial-block.addBlock');
        Route::delete('/{user}/financial-status/{financialBlock}', [UserFinancialBlockController::class, 'removeBlock'])->name('user.financial-block.deleteBlock');

        Route::get('/financial-status/mass-block', [UserFinancialBlockController::class, 'createMassBlock'])->name('user.financial-block.create-mass-block');
        Route::post('/financial-status/mass-block', [UserFinancialBlockController::class, 'storeMassBlock'])->name('user.financial-block.store-mass-block');

        Route::get('/{user}/wallets', [UserWalletController::class, 'userWallets'])->name('wallet.index')->can('wallet');
        Route::get('/{user}/wallets/{wallet}/{type}', [UserWalletController::class, 'walletDetails'])->name('wallet.detail')->can('wallet');

        Route::get('/{user}/inquiry', [InquiryController::class, 'userDetails'])->name('inquiry.user-details')->can('user.index');

        Route::get('/{user}/login-as-user/', [UserController::class, 'loginAsUser'])->name('user.login-as-user')->can('user.login-as-customer');
    });


    Route::get('/inquiry', [InquiryController::class, 'index'])->name('inquiry.index')->can('user.index');
    Route::post('/inquiry', [InquiryController::class, 'submit'])->name('inquiry.submit')->can('user.index');
    Route::post('/inquiry/create-wallet', [InquiryController::class, 'createWallet'])->name('inquiry.create-wallet')->can('user.index');

    Route::get('/role/{admin}', [AdminRoleController::class, 'edit'])->name('role.user.edit')->can('roles.permissions');
    Route::patch('/role/{admin}', [AdminRoleController::class, 'update'])->name('role.user.update')->can('roles.permissions');
    Route::get('/roles', [RoleController::class, 'index'])->name('role.index')->can('roles.permissions');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('role.create')->can('roles.permissions');
    Route::post('/roles', [RoleController::class, 'store'])->name('role.store')->can('roles.permissions');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('role.edit')->can('roles.permissions');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('role.update')->can('roles.permissions');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('role.destroy')->can('roles.permissions');

    Route::get('/permissions', [PermissionController::class, 'index'])->name('permission.index')->can('roles.permissions');
    Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permission.edit')->can('roles.permissions');
    Route::patch('/permissions/{permission}', [PermissionController::class, 'update'])->name('permission.update')->can('roles.permissions');

    Route::get('/referral-codes', [ReferralCodeController::class, 'index'])->name('referral_code.index')->can('referral_code.index');
    Route::get('/referral-codes/create', [ReferralCodeController::class, 'create'])->name('referral_code.create')->can('referral_code.create');
    Route::get('/referral-codes/{referral_code}', [ReferralCodeController::class, 'showUsage'])->name('referral_code.show')->can('referral_code.index');
    Route::get('/referral-codes/referred-user/{user}/transactions', [ReferralCodeController::class, 'showTransactionsForReferredUser'])->name('referral_code.showTransactionsForReferredUser')->can('referral_code.index');
    Route::post('/referral-codes', [ReferralCodeController::class, 'store'])->name('referral_code.store')->can('referral_code.create');
    Route::get('/referral-codes/{referral_code}/edit', [ReferralCodeController::class, 'edit'])->name('referral_code.edit')->can('referral_code.edit');
    Route::patch('/referral-codes/{referral_code}', [ReferralCodeController::class, 'update'])->name('referral_code.update')->can('referral_code.edit');
    Route::delete('/referral-codes/{referral_code}', [ReferralCodeController::class, 'destroy'])->name('referral_code.destroy')->can('referral_code.edit');

    Route::prefix('exchange')->group(function () {
        Route::get('/currencies', [CurrencyController::class, 'index'])->name('currency.index')->can('currency');
        Route::get('/currencies/create', [CurrencyController::class, 'create'])->name('currency.create')->can('currency');
        Route::post('/currencies', [CurrencyController::class, 'store'])->name('currency.store')->can('currency');
        Route::get('/currencies/{currency}', [CurrencyController::class, 'show'])->name('currency.show')->can('currency');
        Route::get('/currencies/{currency}/edit', [CurrencyController::class, 'edit'])->name('currency.edit')->can('currency');
        Route::patch('/currencies/{currency}', [CurrencyController::class, 'update'])->name('currency.update')->can('currency');
        Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currency.destroy')->can('currency');

        Route::get('/currencies/{currency}/chains', [CurrencyChainController::class, 'getChains'])->name('currency.chains.edit')->can('currency');
        Route::get('/currencies/{currency}/chains/create', [CurrencyChainController::class, 'createChain'])->name('currency.chains.create')->can('currency');
        Route::post('/currencies/{currency}/chains', [CurrencyChainController::class, 'storeChain'])->name('currency.chains.store')->can('currency');
        Route::patch('/currencies/{currency}/chains', [CurrencyChainController::class, 'updateChains'])->name('currency.chains.update')->can('currency');

        Route::get('/currencies/{currency}/nodeprovider', [NodeProviderController::class, 'edit'])->name('currency.nodeprovider.edit')->can('currency');
        Route::patch('/currencies/{currency}/nodeprovider', [NodeProviderController::class, 'update'])->name('currency.nodeprovider.update')->can('currency');

        Route::get('/markets', [MarketController::class, 'index'])->name('market.index')->can('market');
        Route::get('/markets/create', [MarketController::class, 'create'])->name('market.create')->can('market');
        Route::post('/markets', [MarketController::class, 'store'])->name('market.store')->can('market');
        Route::get('/markets/{market}/edit', [MarketController::class, 'edit'])->name('market.edit')->can('market');
        Route::patch('/markets/{market}', [MarketController::class, 'update'])->name('market.update')->can('market');
        Route::get('/markets/{market}/coinex-min-otc', [MarketController::class, 'getCoinexMinOtcAmount'])->name('market.coinex-min-otc')->can('market');

        Route::get('/wallets/localWallets', [ExchangeWalletController::class, 'localWallets'])->name('exchange.local-wallet');

        Route::get('/wallets/hotWallets', [ExchangeWalletController::class, 'hotWallets'])->name('exchange.hot-wallet');
        Route::post('/wallets/refresh-hot-wallet-balance', [ExchangeWalletController::class, 'refreshHotWalletBalance'])->name('refresh.balance');
        Route::get('/wallets/hotWallets/assets-gathering-to-cold-wallet', [ExchangeWalletController::class, 'assetsGatheringToColdWallet'])->name('wallet.assets-gathering-to-cold-wallet');
    });

    Route::prefix('ref-exchanges')->group(function () {
        Route::get('/', [RefExchangeController::class, 'index'])->name('exchange.index')->can('ref-exchanges');
        Route::get('/edit/{id}', [RefExchangeController::class, 'edit'])->name('exchange.ref.edit')->can('ref-exchanges');
        Route::patch('/update/{id}', [RefExchangeController::class, 'update'])->name('exchange.ref.update')->can('ref-exchanges');
        Route::get('/wallets/{exchange}', [RefExchangeController::class, 'exchangeWallets'])->name('exchange.wallet');
        Route::get('/assets-gathering-to-hd-wallet', [RefExchangeAssetsWithdrawalController::class, 'index'])->name('ref-exchange.assets-gathering-to-hd-wallet.index');
        Route::get('/assets-gathering-to-hd-wallet/create', [RefExchangeAssetsWithdrawalController::class, 'create'])->name('ref-exchange.assets-gathering-to-hd-wallet.create');
        Route::post('/assets-gathering-to-hd-wallet/', [RefExchangeAssetsWithdrawalController::class, 'store'])->name('ref-exchange.assets-gathering-to-hd-wallet.store');
        Route::get('/assets-gathering-to-hd-wallet/pending-withdrawal/', [RefExchangeAssetsWithdrawalController::class, 'getPendingRefExchangeWithdrawal'])->name('ref-exchange.assets-gathering-to-hd-wallet.pending-withdrawal');
        Route::post('/assets-gathering-to-hd-wallet/bulk-aggregate/', [RefExchangeAssetsWithdrawalController::class, 'bulkAggregate'])->name('ref-exchange.assets-gathering-to-hd-wallet.bulk-aggregate');

        // Currency withdrawal settings routes
        Route::patch('/currency/{currencyId}/withdrawal-settings', [RefExchangeAssetsWithdrawalController::class, 'updateCurrencyWithdrawalSettings'])->name('ref-exchange.currency.withdrawal-settings.update');
        Route::post('/currency/withdrawal-settings/bulk', [RefExchangeAssetsWithdrawalController::class, 'bulkUpdateCurrencyWithdrawalSettings'])->name('ref-exchange.currency.withdrawal-settings.bulk-update');
        Route::post('/currency/{currencyId}/toggle-withdrawal', [RefExchangeAssetsWithdrawalController::class, 'toggleCurrencyWithdrawalStatus'])->name('ref-exchange.currency.toggle-withdrawal');
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('transaction.index')->can('transaction');
        Route::post('/excel-export', [TransactionController::class, 'excelExport'])->name('transaction.excel-export')->can('transaction');
    });

    Route::prefix('otc_orders')->group(function () {
        Route::get('/', [OTCOrderController::class, 'index'])->name('otc_orders.index')->can('otc_order');
        Route::post('/excel-export', [OTCOrderController::class, 'excelExport'])->name('otc_orders.excel-export')->can('otc_order');
        Route::post('/{otcOrderId}/trigger-ref-exchange-sell', [OTCOrderController::class, 'triggerRefExchangeSell'])->name('otc_orders.trigger-ref-exchange-sell')->can('otc_order');
        Route::post('/{otcOrderId}/reset-ref-exchange-sell', [OTCOrderController::class, 'resetRefExchangeSellStatus'])->name('otc_orders.reset-ref-exchange-sell')->can('otc_order');
    });

    Route::prefix('spot')->group(function () {
        Route::get('/trades', [SpotTradeController::class, 'index'])->name('spot_trades.index')->can('spot');
        Route::post('/trades/excel-export', [SpotTradeController::class, 'excelExport'])->name('spot_trade.excel-export')->can('spot');

        Route::get('/orders', [SpotOrderController::class, 'index'])->name('spot_orders.index')->can('spot');
        Route::post('/orders/excel-export', [SpotOrderController::class, 'excelExport'])->name('spot_orders.excel-export')->can('spot');
        Route::post('/orders/{spotOrder}/cancel', [SpotOrderController::class, 'cancelOrder'])->name('spot_orders.cancel')->can('spot');
        Route::post('/orders/cancel-all-open', [SpotOrderController::class, 'cancelAllOpenOrders'])->name('spot_orders.cancel-all-open')->can('spot');
    });

    Route::prefix('deposits')->group(function () {
        Route::get('/', [DepositController::class, 'index'])->name('deposit.index')->can('deposit');
        Route::post('/excel-export', [DepositController::class, 'excelExport'])->name('deposit.excel-export')->can('deposit');
        Route::post('/{deposit}/approve', [DepositController::class, 'approve'])->name('deposit.approve')->can('deposit');
    });

    Route::prefix('withdrawal')->group(function () {
        Route::get('/', [WithdrawalController::class, 'index'])->name('withdrawal.index')->can('withdrawal');
        Route::get('/check-withdrawal/{withdrawal}', [WithdrawalController::class, 'checkWithdrawal'])->name('withdrawal.check-withdrawal')->can('withdrawal');
        Route::get('/{withdraw}/confirm', [WithdrawalController::class, 'confirmWithdrawal'])->name('withdrawal.confirm-withdrawal')->can('withdrawal');
        Route::get('/{withdraw}/cancel', [WithdrawalController::class, 'cancelWithdrawal'])->name('withdrawal.cancel-withdrawal')->can('withdrawal');
        Route::post('/excel-export', [WithdrawalController::class, 'excelExport'])->name('withdrawal.excel-export')->can('withdrawal');
        Route::post('/{withdrawal}/redispatch-job', [WithdrawalController::class, 'redispatchWithdrawalJob'])->name('withdrawal.redispatch-job')->can('withdrawal');
    });

    Route::get('/internal-settings', [InternalSettingController::class, 'index'])->name('internal.setting.index')->can('setting.int.index');
    Route::post('/internal-settings/update-permissions', [InternalSettingController::class, 'updatePermissions'])->name('setting.int.update-permissions')->can('setting.int.index');
    Route::post('/internal-settings/update-otc-commission', [InternalSettingController::class, 'updateOTCCommission'])->name('setting.int.update-otc-commission')->can('setting.int.index');
    Route::post('/internal-settings/update-spot-commission', [InternalSettingController::class, 'updateSpotCommission'])->name('setting.int.update-spot-commission')->can('setting.int.index');

    Route::post('/internal-settings/update-referral-setting', [InternalSettingController::class, 'updateReferralSetting'])->name('setting.int.update-referral-setting')->can('setting.int.index');
    Route::post('/internal-settings/update-exchange-withdrawal-setting', [InternalSettingController::class, 'updateExchangeWithdrawalSetting'])->name('setting.int.update-exchange-withdrawal-setting')->can('setting.int.index');
    Route::post('/internal-settings/update-spot-settings', [InternalSettingController::class, 'updateSpotSettings'])->name('setting.int.update-spot-settings')->can('setting.int.index');
    Route::post('/internal-settings/update-otc-settings', [InternalSettingController::class, 'updateOtcSettings'])->name('setting.int.update-otc-settings')->can('setting.int.index');

    Route::get('/external-settings', [ExternalSettingController::class, 'index'])->name('external-setting.index')->can('setting.ext.index');
    Route::post('/external-settings/update-ref-address', [ExternalSettingController::class, 'updateRefAddress'])->name('setting.ext.update-ref-address')->can('setting.ext.index');

    Route::get('/docker', [DockerController::class, 'index'])->name('docker.index')->can('setting.int.index');
    Route::post('/docker/restart-exchange-listen', [DockerController::class, 'restartExchangeListen'])->name('docker.restart-exchange-listen')->can('setting.int.index');
    Route::get('/docker/container-status', [DockerController::class, 'getContainerStatus'])->name('docker.container-status')->can('setting.int.index');
    Route::get('/docker/exchange-listen-logs', [DockerController::class, 'getExchangeListenLogs'])->name('docker.exchange-listen-logs')->can('setting.int.index');
    Route::get('/docker/debug-paths', [DockerController::class, 'debugDockerPaths'])->name('docker.debug-paths')->can('setting.int.index');

    // Sweeper Containers Management
    Route::get('/docker/sweeper-status', [DockerController::class, 'getSweeperContainersStatus'])->name('docker.sweeper-status')->can('sweeper.management');
    Route::post('/docker/sweeper-start', [DockerController::class, 'startSweeperContainers'])->name('docker.sweeper-start')->can('sweeper.management');
    Route::post('/docker/sweeper-stop', [DockerController::class, 'stopSweeperContainers'])->name('docker.sweeper-stop')->can('sweeper.management');

    # *********SPOT BOT SETTINGS*********#
    Route::prefix('bot')->group(function () {
        Route::get('/settings', [SpotBotSettingController::class, 'index'])->name('setting.spot-bot.index')->can('setting.int.index');
        Route::get('/settings/{currency}/edit', [SpotBotSettingController::class, 'edit'])->name('setting.spot-bot.edit')->can('setting.int.index');
        Route::put('/settings/{currency}', [SpotBotSettingController::class, 'update'])->name('setting.spot-bot.update')->can('setting.int.index');
    });

    Route::prefix('wallet')->group(function () {
        Route::get('increase-credit', [WalletController::class, 'increaseCreditForm'])->name('wallet.increase-credit.form')->can('wallet');
        Route::post('increase-credit', [WalletController::class, 'increaseCredit'])->name('wallet.increase-credit')->can('wallet');
        Route::get('decrease-credit', [WalletController::class, 'decreaseCreditForm'])->name('wallet.decrease-credit.form')->can('wallet');
        Route::post('decrease-credit', [WalletController::class, 'decreaseCredit'])->name('wallet.decrease-credit')->can('wallet');
        Route::get('{wallet}/user/{user}/block-balance', [WalletController::class, 'blockBalanceForm'])->name('wallet.block-balance.form')->can('wallet');
        Route::post('{wallet}/block-balance', [WalletController::class, 'blockBalance'])->name('wallet.block-balance')->can('wallet');
        Route::get('{wallet}/user/{user}/unblock-balance', [WalletController::class, 'unblockBalanceForm'])->name('wallet.unblock-balance.form')->can('wallet');
        Route::post('{wallet}/unblock-balance', [WalletController::class, 'unblockBalance'])->name('wallet.unblock-balance')->can('wallet');
        Route::post('{wallet}/create-chain-address/{chain_name}', [WalletController::class, 'createExchangeWalletChain'])->name('wallet.create-chain-address');
        Route::post('update-chain-address/{wallet_chain}', [WalletController::class, 'updateExchangeWalletChain'])->name('wallet.update-chain-address');
        Route::get('{user}/{wallet}/refresh', [WalletController::class, 'refresh'])->name('wallet.refresh');
        Route::get('{user}/chain/{walletChain}/refresh', [WalletController::class, 'refreshByChain'])->name('wallet.refresh-by-chain');
        Route::post('generate-address', [WalletController::class, 'generateAddressFromHdWallet'])->name('wallet.generate-address')->can('wallet');
        Route::post('create-wallet-chains', [WalletController::class, 'createWalletChains'])->name('wallet.create-chains')->can('wallet');
    });

    // HD Wallet Balance management
    Route::prefix('hd-wallet')->group(function () {
        Route::get('index', [HdWalletIndexReportController::class, 'index'])->name('hd-wallet.index')->can('hd_wallet');
        Route::post('balance-data', [HdWalletIndexReportController::class, 'getBalanceData'])->name('hd-wallet.balance-data')->can('hd_wallet');
        Route::post('currency-chains', [HdWalletIndexReportController::class, 'getCurrencyChains'])->name('hd-wallet.currency-chains')->can('hd_wallet');
        Route::post('export-excel', [HdWalletIndexReportController::class, 'exportExcel'])->name('hd-wallet.export-excel')->can('hd_wallet');
        Route::post('query-blockchain', [HdWalletIndexReportController::class, 'queryBlockchainBalance'])->name('hd-wallet.query-blockchain')->can('hd_wallet');
        Route::post('sweep-selected', [HdWalletIndexReportController::class, 'sweepSelectedIndices'])->name('hd-wallet.sweep-selected')->can('hd_wallet');
        Route::post('fund-selected', [HdWalletIndexReportController::class, 'fundSelectedIndices'])->name('hd-wallet.fund-selected')->can('hd_wallet');
        Route::post('estimate-gas-funding', [HdWalletIndexReportController::class, 'estimateGasFunding'])->name('hd-wallet.estimate-gas-funding')->can('hd_wallet');
        Route::get('sweeper-wallets', [HdWalletIndexReportController::class, 'getSweeperWallets'])->name('hd-wallet.sweeper-wallets')->can('hd_wallet');
        Route::post('start-sync', [HdWalletIndexReportController::class, 'startSync'])->name('hd-wallet.start-sync')->can('hd_wallet');
        Route::get('sync-progress/{syncId}', [HdWalletIndexReportController::class, 'getSyncProgress'])->name('hd-wallet.sync-progress')->can('hd_wallet');

        Route::get('currencies', [HdWalletCurrencyController::class, 'index'])->name('hd-wallet.currencies')->can('hd_wallet');
        Route::post('currencies/create', [HdWalletCurrencyController::class, 'create'])->name('hd-wallet.currencies.create')->can('hd_wallet');
        Route::post('currencies/update', [HdWalletCurrencyController::class, 'update'])->name('hd-wallet.currencies.update')->can('hd_wallet');
        Route::post('currencies/delete', [HdWalletCurrencyController::class, 'destroy'])->name('hd-wallet.currencies.delete')->can('hd_wallet');
    });


    Route::prefix('locked-balances')->group(function () {
        Route::get('/', [LockedBalanceDetailController::class, 'index'])->name('locked-balance.index')->can('wallet');
    });

    Route::prefix('report')->group(function () {
        Route::get('deposit', [DepositReportController::class, 'index'])->name('report.deposit')->can('report');
        Route::get('withdrawal', [WithdrawalReportController::class, 'index'])->name('report.withdrawal')->can('report');
        Route::get('user-registration-report', [UserRegistrationReportController::class, 'index'])->name('report.getUserRegistrationState')->can('user.index');
        Route::get('user-registration-report/month', [UserRegistrationReportController::class, 'getUserRegistrationState'])->name('report.getUserRegistrationState.month')->can('user.index');
        Route::get('ref-exchange/bought-history', [RefExchangeController::class, 'boughtHistory'])->name('report.ref-exchange.bought-history')->can('report');
    });

    // *********STOCKS*********//
    Route::prefix('stocks')->group(function () {
        Route::get('/', [StockController::class, 'index'])->name('stock.index')->can('stock');
        Route::get('/create', [StockController::class, 'create'])->name('stock.create')->can('stock');
        Route::post('/', [StockController::class, 'store'])->name('stock.store')->can('stock');
        Route::get('/{stock}', [StockController::class, 'show'])->name('stock.show')->can('stock');
        Route::get('/{stock}/edit', [StockController::class, 'edit'])->name('stock.edit')->can('stock');
        Route::patch('/{stock}', [StockController::class, 'update'])->name('stock.update')->can('stock');
        Route::delete('/{stock}', [StockController::class, 'destroy'])->name('stock.destroy')->can('stock');
    });

    // *********STOCK CONTRACTS*********//
    Route::prefix('stock-contracts')->group(function () {
        Route::get('/', [StockContractController::class, 'index'])->name('stock-contract.index')->can('stock');
        Route::get('/create', [StockContractController::class, 'create'])->name('stock-contract.create')->can('stock');
        Route::post('/', [StockContractController::class, 'store'])->name('stock-contract.store')->can('stock');
        Route::get('/{stockContract}', [StockContractController::class, 'show'])->name('stock-contract.show')->can('stock');
        Route::get('/{stockContract}/edit', [StockContractController::class, 'edit'])->name('stock-contract.edit')->can('stock');
        Route::patch('/{stockContract}', [StockContractController::class, 'update'])->name('stock-contract.update')->can('stock');
        Route::post('/{stockContract}/regenerate-pdf', [StockContractController::class, 'generateContractPdfIfNotExists'])->name('stock-contract.regenerate-pdf')->can('stock');
    });

    //     *********API SYSTEMS*********//
    Route::prefix('api-systems')->group(function () {
        Route::get('/', [ApiSystemController::class, 'index'])->name('api-system.index')->can('api-system');
        Route::get('/create', [ApiSystemController::class, 'create'])->name('api-system.create')->can('api-system');
        Route::post('/', [ApiSystemController::class, 'store'])->name('api-system.store')->can('api-system');
        Route::get('/{system}', [ApiSystemController::class, 'show'])->name('api-system.show')->can('api-system');
        Route::get('/{system}/edit', [ApiSystemController::class, 'edit'])->name('api-system.edit')->can('api-system');
        Route::patch('/{system}', [ApiSystemController::class, 'update'])->name('api-system.update')->can('api-system');
        Route::patch('/{system}/toggle-status', [ApiSystemController::class, 'toggleStatus'])->name('api-system.toggle-status')->can('api-system');
        Route::delete('/{system}', [ApiSystemController::class, 'destroy'])->name('api-system.destroy')->can('api-system');
        Route::get('/statistics/overview', [ApiSystemController::class, 'statistics'])->name('api-system.statistics')->can('api-system');

        // API System Tokens
        Route::prefix('{system}/tokens')->group(function () {
            Route::get('/', [ApiSystemTokenController::class, 'index'])->name('api-system.tokens.index')->can('api-system');
            Route::get('/create', [ApiSystemTokenController::class, 'create'])->name('api-system.tokens.create')->can('api-system');
            Route::post('/', [ApiSystemTokenController::class, 'store'])->name('api-system.tokens.store')->can('api-system');
            Route::get('/{token}', [ApiSystemTokenController::class, 'show'])->name('api-system.tokens.show')->can('api-system');
            Route::get('/{token}/edit', [ApiSystemTokenController::class, 'edit'])->name('api-system.tokens.edit')->can('api-system');
            Route::patch('/{token}', [ApiSystemTokenController::class, 'update'])->name('api-system.tokens.update')->can('api-system');
            Route::post('/{token}/toggle-status', [ApiSystemTokenController::class, 'toggleStatus'])->name('api-system.tokens.toggle-status')->can('api-system');
            Route::post('/{token}/regenerate', [ApiSystemTokenController::class, 'regenerate'])->name('api-system.tokens.regenerate')->can('api-system');
            Route::delete('/{token}', [ApiSystemTokenController::class, 'destroy'])->name('api-system.tokens.destroy')->can('api-system');
            Route::get('/{token}/usage-stats', [ApiSystemTokenController::class, 'getUsageStats'])->name('api-system.tokens.usage-stats')->can('api-system');
        });
    });
});
