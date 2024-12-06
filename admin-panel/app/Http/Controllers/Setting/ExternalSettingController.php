<?php

namespace App\Http\Controllers\Setting;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ExternalSettingController extends Controller
{
    public function index()
    {

    }

    public function updateRefAddress(Request $request)
    {


        Toast::message('آدرس reference با موفقیت تغییر یافت.')->success()->notify();

        return redirect()->back();
    }
}
