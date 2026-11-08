<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Support\AdminTheme;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function setTheme(Request $request)
    {
        $theme = $request->input('theme', AdminTheme::LIGHT);
        if (! in_array($theme, [AdminTheme::LIGHT, AdminTheme::DARK, AdminTheme::SYSTEM], true)) {
            $theme = AdminTheme::LIGHT;
        }

        session(['theme' => $theme]);

        return redirect()->back();
    }
}
