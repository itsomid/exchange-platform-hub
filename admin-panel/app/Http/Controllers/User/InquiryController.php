<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\User;
use App\Models\Withdrawal;

class InquiryController extends Controller
{
    public function index()
    {
        return view('dashboard.inquiry_user.index');
    }

    public function submit()
    {
        request()->validate([
            'email' => ['required'],
        ]);
        $email = request()->input('email');
        $user = User::query()->where('email', 'LIKE', '%' . $email . '%')->first();

        return redirect()->route('admin.inquiry.user-details',['user'=>$user]);
        return view('dashboard.inquiry_user.index')->with([
            'user' => $user,
        ]);
    }

    public function userDetails(User $user)
    {
        $otcOrders = OTCOrder::query()->whereUserId($user->id)->with(['market', 'transactions'])->orderBy('created_at', 'desc')->take(5)->get();
        $totalOtcOrdersCount = OTCOrder::query()->whereUserId($user->id)->count();

        $withdraws = Withdrawal::query()->whereUserId($user->id)->with(['user', 'currency', 'transaction'])->orderBy('created_at', 'desc')->take(5)->get();
        $totalWithdrawsCount = Withdrawal::query()->whereUserId($user->id)->count();

        $deposits = Deposit::query()->whereUserId($user->id)->with(['user','currency', 'transaction'])->orderBy('created_at', 'desc')->take(5)->get();
        $totalDepositsCount = Deposit::query()->whereUserId($user->id)->count();

        return view('dashboard.inquiry_user.full-report', [
            'otcOrders' => $otcOrders,
            'totalOtcOrdersCount' => $totalOtcOrdersCount,
            'withdraws' => $withdraws,
            'totalWithdrawsCount' => $totalWithdrawsCount,
            'deposits' => $deposits,
            'totalDepositsCount' => $totalDepositsCount,
            'user' => $user
        ]);
    }
}
