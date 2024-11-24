<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function setTheme(Request $request)
    {
        $theme = $request->input('theme', 'light'); // Default to 'light' if no theme is provided
        $theme = in_array($theme, ['light', 'dark']) ? $theme : 'light'; // Validate the theme

        // Store the theme in session
        session(['theme' => $theme]);

        return redirect()->back();
        //        return response()->json(['status' => 'success', 'theme' => $theme]);
    }
}
