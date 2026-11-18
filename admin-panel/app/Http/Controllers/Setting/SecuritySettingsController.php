<?php

namespace App\Http\Controllers\Setting;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Services\Security\InactiveExchangeSessionService;

class SecuritySettingsController extends Controller
{
    public function index(InactiveExchangeSessionService $sessions)
    {
        return view('dashboard.setting.security.index', [
            'stats' => $sessions->summary(),
        ]);
    }

    public function revokeIdleSessions(InactiveExchangeSessionService $sessions)
    {
        $result = $sessions->revoke();

        if ($result['deleted_tokens'] === 0) {
            Toast::message('نشست غیرفعالی برای حذف پیدا نشد.')->warning()->notify();

            return redirect()->route('admin.security-settings.index');
        }

        Toast::message(sprintf(
            'توکن %s کاربر غیرفعال منقضی و حذف شد (%s توکن). کاربران فعال %d دقیقه اخیر سر جایشان ماندند.',
            number_format($result['affected_users']),
            number_format($result['deleted_tokens']),
            InactiveExchangeSessionService::IDLE_MINUTES
        ))->success()->duration(5)->notify();

        return redirect()->route('admin.security-settings.index');
    }
}
