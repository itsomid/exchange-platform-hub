<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\referralCode\StoreReferralCodeRequest;
use App\Http\Requests\referralCode\UpdateReferralCodeRequest;
use App\Models\Admin;
use App\Models\ReferralCode;

class ReferralCodeController extends Controller
{
    public function index()
    {
         $referralCodes = ReferralCode::filterBy(request()->all())
            ->with('user')
            ->withCount('registeredUsers')
            ->withCount('referralCodeUsage')
            ->withSum('transactions', 'amount')
            ->get();
        $totalTransactionSum = $referralCodes->sum('transactions_sum_amount');
        $totalRegisteredUsers = $referralCodes->sum('registered_users_count');

        return view('dashboard.referral_code.index', [
            'referralCodes' => $referralCodes,
            'totalTransactionSum'=>$totalTransactionSum,
            'totalRegisteredUsers'=>$totalRegisteredUsers
        ]);
    }

    public function create()
    {
        $admins = Admin::select('id','mobile', 'first_name', 'last_name')->get();
        $sampleReferralCode = ReferralCode::generateReferralCode();
        return view('dashboard.referral_code.create')
            ->with(['admins' => $admins,'sampleReferralCode'=>$sampleReferralCode]);
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
}
