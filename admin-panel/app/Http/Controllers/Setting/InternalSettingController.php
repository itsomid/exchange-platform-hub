<?php

namespace App\Http\Controllers\Setting;

use App\Data\PermissionList;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class InternalSettingController extends Controller
{
    protected $walletService;
    protected $bitexroomUserId;
    public function __construct(WalletService $walletService)
    {
        $this->bitexroomUserId = config('bitexroom.user_id', 1);
        $this->walletService = $walletService;
    }
    public function index()
    {
        $last3permissions = Permission::query()->latest()->take(3)->get();
        $otcBuyFee = Setting::where('key', 'otc_buy_fee')->first();
        $otcSellFee = Setting::where('key', 'otc_sell_fee')->first();
        $spotMakerFee = Setting::where('key', 'spot_maker_fee')->first();
        $spotTakerFee = Setting::where('key', 'spot_taker_fee')->first();
        $referralProfitStatus = Setting::where('key', 'referral_profit_status')->first();
        $referralProfitPercentage = Setting::where('key', 'referral_profit_percentage')->first();
        $referralUsageLimitCount = Setting::where('key', 'referral_usage_limit_count')->first();
        $exchangeWithdrawalPeriodTime = Setting::where('key', 'exchange_withdrawal_period_time')->first();
        $exchangeWithdrawalPeriodBuy = Setting::where('key', 'exchange_withdrawal_period_buy')->first();
        $exchangeWithdrawalType = Setting::where('key', 'exchange_withdrawal_type')->first();
        $exchangeWithdrawalStatus = Setting::where('key', 'exchange_withdrawal_status')->first();
        $spotTickerEnabled = Setting::where('key', 'spot_ticker_enabled')->first();
        $orderMatchingEnabled = Setting::where('key', 'order_matching_enabled')->first();
        $spotTradingEnabled = Setting::where('key', 'spot_trading_enabled')->first();
        $otcTradingEnabled = Setting::where('key', 'otc_trading_enabled')->first();

        $exchangeWalletChains = $this->walletService->getExchangeAllWalletChain();

        return view('dashboard.setting.internal.index', [
            'last3permissions' => $last3permissions,
            'otcBuyFee' => $otcBuyFee,
            'otcSellFee' => $otcSellFee,
            'spotMakerFee' => $spotMakerFee,
            'spotTakerFee' => $spotTakerFee,
            'referralProfitStatus' => $referralProfitStatus,
            'referralProfitPercentage' => $referralProfitPercentage,
            'referralUsageLimitCount' => $referralUsageLimitCount,
            'exchangeWithdrawalPeriodTime' => $exchangeWithdrawalPeriodTime,
            'exchangeWithdrawalPeriodBuy' => $exchangeWithdrawalPeriodBuy,
            'exchangeWithdrawalType' => $exchangeWithdrawalType,
            'exchangeWithdrawalStatus' => $exchangeWithdrawalStatus,
            'spotTickerEnabled' => $spotTickerEnabled,
            'orderMatchingEnabled' => $orderMatchingEnabled,
            'spotTradingEnabled' => $spotTradingEnabled,
            'otcTradingEnabled' => $otcTradingEnabled,
            'exchangeWalletChains' => $exchangeWalletChains,

        ]);
    }

    public function updateReferralSetting(Request $request)
    {
        // Validate the input
        $request->validate([
            'referral_profit_status' => 'nullable|boolean',
            'referral_profit_percentage' => 'required|numeric|min:0|max:100',
            'referral_usage_limit_count' => 'required|numeric|min:0|max:1000',
        ]);

        // Update referral profit status
        Setting::updateOrCreate(
            ['key' => 'referral_profit_status'],
            [
                'value' => $request->has('referral_profit_status') ? $request->input('referral_profit_status') : false,
                'name' => 'وضعیت فعال‌سازی رفرال',
                'type' => 'boolean'
            ]
        );

        // Update referral profit percentage
        Setting::updateOrCreate(
            ['key' => 'referral_profit_percentage'],
            [
                'value' => $request->input('referral_profit_percentage'),
                'name' => 'نرخ کارمزد اهدایی به کاربر از طریق کد دعوت',
                'type' => 'integer'
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'referral_usage_limit_count'],
            [
                'value' => $request->input('referral_usage_limit_count'),
                'name' => 'حداکثر تعداد استفاده کاربر از کد دعوت',
                'type' => 'integer'
            ]
        );
        Toast::message('تنظیمات رفرال با موفقیت ذخیره شد')->success()->notify();
        // Redirect with success message
        return redirect()->back();
    }

    
    public function updateOTCCommission(Request $request)
    {
        // Validate the input
        $request->validate([
            'otc_buy_fee' => 'required|numeric|min:0',
            'otc_sell_fee' => 'required|numeric|min:0',
        ]);

        // Update OTC buy fee
        Setting::updateOrCreate(
            ['key' => 'otc_buy_fee'],
            ['value' => $request->input('otc_buy_fee')]
        );

        // Update OTC sell fee
        Setting::updateOrCreate(
            ['key' => 'otc_sell_fee'],
            ['value' => $request->input('otc_sell_fee')]
        );
        Toast::message('تنظیمات OTC با موفقیت ذخیره شد')->success()->notify();
        // Redirect with success message
        return redirect()->back();
    }
    
    public function updateSpotCommission(Request $request)
    {
        // Validate the input
        $request->validate([
            'spot_maker_fee' => 'required|numeric|min:0',
            'spot_taker_fee' => 'required|numeric|min:0',
        ]);

        // Update spot maker fee
        Setting::updateOrCreate(
            ['key' => 'spot_maker_fee'],
            ['value' => $request->input('spot_maker_fee')]
        );

        // Update spot taker fee
        Setting::updateOrCreate(
            ['key' => 'spot_taker_fee'],
            ['value' => $request->input('spot_taker_fee')]
        );
        Toast::message('تنظیمات فروش اسپات با موفقیت ذخیره شد')->success()->notify();
        // Redirect with success message
        return redirect()->back();
    }
    
    public function updatePermissions()
    {
        $permissions = [];
        $currentPermissions = Permission::all();

        foreach (PermissionList::get() as $permission) {
            if (!$currentPermissions->where('name', $permission[0])->first()) {
                $permissions[] = Permission::Create(['name' => $permission[0], 'persian_name' => $permission[1]]);
            }
        }

        Toast::message('دسترسی های جدید افزوده شدند')->success()->notify();

        return redirect()->route('admin.internal.setting.index');
    }

    public function updateExchangeWithdrawalSetting(Request $request)
    {
        $request->validate([
            'exchange_withdrawal_period_time' => 'required|numeric|min:1',
            'exchange_withdrawal_period_buy' => 'required|numeric|min:2',
        ]);

        // Update OTC buy fee
        Setting::updateOrCreate(
            ['key' => 'exchange_withdrawal_period_time'],
            ['value' => $request->input('exchange_withdrawal_period_time')]
        );

        // Update OTC sell fee
        Setting::updateOrCreate(
            ['key' => 'exchange_withdrawal_period_buy'],
            ['value' => $request->input('exchange_withdrawal_period_buy')]
        );

        Setting::updateOrCreate(
            ['key' => 'exchange_withdrawal_type'],
            [
                'value' => $request->input('exchange_withdrawal_type'),
                'type' => 'string',
                'name' => '(زمان/تعداد) مدل تجمیع و برداشت از صرافی مرجع',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'exchange_withdrawal_status'],
            [
                'value' => $request->has('exchange_withdrawal_status') ? $request->input('exchange_withdrawal_status') : false,
                'name' => 'وضعیت فعال‌سازی فرآیند تجمیع',
                'type' => 'boolean'
            ]
        );


        Toast::message('تنظیمات فرآیند تجمیع با موفقیت ذخیره شد.')->success()->notify();
        // Redirect with success message
        return redirect()->back();
    }

    public function updateSpotSettings(Request $request)
    {
        // Update spot trading enabled status
        Setting::updateOrCreate(
            ['key' => 'spot_trading_enabled'],
            [
                'value' => $request->has('spot_trading_enabled') ? $request->input('spot_trading_enabled') : false,
                'name' => 'وضعیت فعال‌سازی معاملات اسپات',
                'type' => 'boolean'
            ]
        );

        // Update spot ticker enabled status
        Setting::updateOrCreate(
            ['key' => 'spot_ticker_enabled'],
            [
                'value' => $request->has('spot_ticker_enabled') ? $request->input('spot_ticker_enabled') : false,
                'name' => 'وضعیت فعال‌سازی Spot Ticker',
                'type' => 'boolean'
            ]
        );

        // Update order matching enabled status
        Setting::updateOrCreate(
            ['key' => 'order_matching_enabled'],
            [
                'value' => $request->has('order_matching_enabled') ? $request->input('order_matching_enabled') : false,
                'name' => 'وضعیت فعال‌سازی Order Matching',
                'type' => 'boolean'
            ]
        );

        Toast::message('تنظیمات اسپات با موفقیت ذخیره شد')->success()->notify();
        // Redirect with success message
        return redirect()->back();
    }

    public function updateOtcSettings(Request $request)
    {
        // Update OTC trading enabled status
        Setting::updateOrCreate(
            ['key' => 'otc_trading_enabled'],
            [
                'value' => $request->has('otc_trading_enabled') ? $request->input('otc_trading_enabled') : false,
                'name' => 'وضعیت فعال‌سازی معاملات OTC',
                'type' => 'boolean'
            ]
        );

        Toast::message('تنظیمات OTC با موفقیت ذخیره شد')->success()->notify();
        // Redirect with success message
        return redirect()->back();
    }
}
