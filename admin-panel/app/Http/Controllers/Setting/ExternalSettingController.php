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
        $ref_address = Setting::query()->where('key', 'ref_base_address')->first()->value;

        return view('dashboard.setting.external.index')
            ->with(['ref_address' => $ref_address]);
    }

    public function updateRefAddress(Request $request)
    {
        $this->validate($request, [
            'ref_address' => ['required', 'url'],
        ]);

        $ref = Setting::query()->where('key', 'ref_base_address')->first();
        $ref->update([
            'value' => $request->ref_address,
        ]);

        cache()->put('setting.refAddress', $request->ref_address);

        Toast::message('آدرس reference با موفقیت تغییر یافت.')->success()->notify();

        return redirect()->back();
    }
}
