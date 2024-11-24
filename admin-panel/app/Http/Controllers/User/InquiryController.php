<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;

class InquiryController extends Controller
{
    public function index()
    {
        return view('dashboard.inquiry_user.index');
    }

    public function submit()
    {
        request()->validate([
            'mobile' => ['required'],
        ]);
        $mobile = request()->input('mobile');
        $user = User::query()->where('mobile', 'LIKE', '%'.$mobile.'%')->first();

        return view('dashboard.inquiry_user.index')->with([
            'user' => $user,
        ]);
    }
}
