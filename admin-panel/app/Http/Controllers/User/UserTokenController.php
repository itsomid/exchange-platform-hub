<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserTokenController extends Controller
{
    public function index(User $user)
    {
        $tokens = $user->tokens()->get();

        return view('dashboard.user.token.index')->with('tokens', $tokens)->with('user', $user);
    }

    public function revoke(User $user, $token)
    {
        $user->tokens()->where('id', $token)->delete();

        return redirect()->back();
    }
}
