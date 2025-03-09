<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\referralCode\StoreReferralCodeRequest;
use App\Http\Requests\referralCode\UpdateReferralCodeRequest;
use App\Models\Admin;
use App\Models\ReferralCode;
use App\Models\ReferralCodeUsage;
use App\Models\Transaction;
use App\Models\User;


class ReferralCodeController extends Controller
{
    public function index()
    {
        $query = ReferralCode::filterBy(request()->all())
            ->with('user')
            ->withSum('transactions', 'amount');

        // Only add withCount if we're not sorting by registered users count
        if (!request()->has('sortByRegisteredUserCount')) {
            $query->withCount('registeredUsers');
        }

        if (!request()->has('sortByReferralCodeUsageCount')) {
            $query->withCount('referralCodeUsage');
        }

        $referralCodes = $query->paginate(30);

        $totalTransactionSum = ReferralCode::filterBy(request()->all())->withSum('transactions', 'amount')->get()->sum('transactions_sum_amount');
        $totalRegisteredUsers = ReferralCode::filterBy(request()->all())->withCount('registeredUsers')->get()->sum('registered_users_count');

        return view('dashboard.referral_code.index', [
            'referralCodes' => $referralCodes,
            'totalTransactionSum' => $totalTransactionSum,
            'totalRegisteredUsers' => $totalRegisteredUsers
        ]);
    }

    public function create()
    {
        $admins = Admin::select('id', 'mobile', 'first_name', 'last_name')->get();
        $sampleReferralCode = ReferralCode::generateReferralCode();
        return view('dashboard.referral_code.create')
            ->with(['admins' => $admins, 'sampleReferralCode' => $sampleReferralCode]);
    }

    public function store(StoreReferralCodeRequest $request)
    {

        ReferralCode::create([
            'code' => $request->input('code'),
            'user_id' => $request->user_id, // Assuming the authenticated user is the introducer
            'introducer_fee' => $request->input('introducer_fee'),
            'friend_fee' => $request->input('friend_fee'),
            'usage_limit' => $request->input('usage_limit'),
        ]);

        Toast::message('کد معرف با موفقیت ایجاد شد')->success()->notify();
        return redirect()->route('admin.referral_code.index');
    }

    public function edit(ReferralCode $referralCode)
    {

        return view('dashboard.referral_code.edit')
            ->with(['referralCode' => $referralCode]);
    }

    public function update(ReferralCode $referralCode, UpdateReferralCodeRequest $request)
    {
        //TODO: create Request


        $referralCode->update($request->validated());

        Toast::message('کد معرف با موفقیت ویرایش شد')->success()->notify();
        return redirect()->route('admin.referral_code.index');
    }

    public function showUsage(ReferralCode $referralCode)
    {


        $referralCode = $referralCode
            ->load(['friendsReferralCodeUsage','introducerReferralCodeUsage', 'registeredUsers', 'introducerTransactions'])
            ->loadCount('registeredUsers')
            ->loadSum('transactions', 'amount');

        $countOfUserHasUsedReferralCode = $referralCode->friendsReferralCodeUsage->groupBy('used_by')->count();
        $conversationRate = ($countOfUserHasUsedReferralCode / ($referralCode->registered_users_count)) * 100;

        return view('dashboard.referral_code.referred-users', [
            'referralCode' => $referralCode,
            'conversationRate' => $conversationRate
        ]);
    }

    public function showTransactionsForReferredUser(User $user)
    {
        $introducerReferralCodeUsage = ReferralCodeUsage::whereUsedBy($user->id)->where('type', 'introducer')->with(['transaction', 'user'])->get();
        $friendReferralCodeUsage = ReferralCodeUsage::whereUsedBy($user->id)->where('type', 'friend')->with(['transaction', 'user'])->get();

        return view('dashboard.referral_code.referred-users-transaction', [
            'user' => $user,
            'introducerReferralCodeUsage' => $introducerReferralCodeUsage,
            'friendReferralCodeUsage' => $friendReferralCodeUsage
        ]);
    }

    public function destroy(ReferralCode $referralCode)
    {
        if ($referralCode->registeredUsers()->exists()) {
            Toast::message('این کد معرف دارای کاربران مرتبط است و نمی‌توان آن را حذف کرد')->danger()->notify();
            return redirect()->route('admin.referral_code.index');
        }

        if ($referralCode->transactions()->exists()) {
            Toast::message('این کد معرف دارای تراکنش‌ها مرتبط است و نمی‌توان آن را حذف کرد')->danger()->notify();
            return redirect()->route('admin.referral_code.index');
        }

        $referralCode->delete();
        Toast::message('کد معرف با موفقیت حذف شد')->success()->notify();
        return redirect()->route('admin.referral_code.index');
    }
}
