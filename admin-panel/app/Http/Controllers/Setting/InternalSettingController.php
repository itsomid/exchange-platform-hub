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

    protected $exchangeUserId;
    public function __construct(WalletService $walletService)
    {
        $this->exchangeUserId = config('exchange.exchange_user_id', 1);
        $this->walletService = $walletService;
    }
    public function index()
    {
        $last3permissions = Permission::query()->latest()->take(3)->get();
        $otcBuyFee = Setting::where('key', 'otc_buy_fee')->first();
        $otcSellFee = Setting::where('key', 'otc_sell_fee')->first();
        $referralProfitStatus = Setting::where('key', 'referral_profit_status')->first();
        $referralProfitPercentage = Setting::where('key', 'referral_profit_percentage')->first();
        $exchangeWithdrawalPeriodTime = Setting::where('key', 'exchange_withdrawal_period_time')->first();
        $exchangeWithdrawalPeriodBuy = Setting::where('key', 'exchange_withdrawal_period_buy')->first();
        $exchangeWithdrawalType = Setting::where('key', 'exchange_withdrawal_type')->first();
        $exchangeWithdrawalStatus = Setting::where('key', 'exchange_withdrawal_status')->first();

        $exchangeWalletChains = $this->walletService->getExchangeAllWalletChain();

        return view('dashboard.setting.internal.index', [
            'last3permissions' => $last3permissions,
            'otcBuyFee' => $otcBuyFee,
            'otcSellFee' => $otcSellFee,
            'referralProfitStatus' => $referralProfitStatus,
            'referralProfitPercentage' => $referralProfitPercentage,
            'exchangeWithdrawalPeriodTime' => $exchangeWithdrawalPeriodTime,
            'exchangeWithdrawalPeriodBuy' => $exchangeWithdrawalPeriodBuy,
            'exchangeWithdrawalType' => $exchangeWithdrawalType,
            'exchangeWithdrawalStatus' => $exchangeWithdrawalStatus,
            'exchangeWalletChains' => $exchangeWalletChains,

        ]);
    }

    public function updateReferralSetting(Request $request)
    {
        // Validate the input
        $request->validate([
            'referral_profit_status' => 'nullable|boolean',
            'referral_profit_percentage' => 'required|numeric|min:0|max:100',
        ]);

        // Update referral profit status
        Setting::updateOrCreate(
            ['key' => 'referral_profit_status'],
            ['value' => $request->has('referral_profit_status') ? $request->input('referral_profit_status'): false]
        );

        // Update referral profit percentage
        Setting::updateOrCreate(
            ['key' => 'referral_profit_percentage'],
            ['value' => $request->input('referral_profit_percentage')]
        );
        Toast::message('تنظیمات رفرال با موفقیت ذخیره شد')->success()->notify();
        // Redirect with success message
        return redirect()->back();
    }

    public function updateOTCSetting(Request $request)
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
            ['value' => $request->input('exchange_withdrawal_type')]
        );

        Setting::updateOrCreate(
            ['key' => 'exchange_withdrawal_status'],
            ['value' => $request->has('exchange_withdrawal_status') ? $request->input('exchange_withdrawal_status'): false]
        );


        Toast::message('تنظیمات فرآیند تجمیع با موفقیت ذخیره شد.')->success()->notify();
        // Redirect with success message
        return redirect()->back();
    }

}
