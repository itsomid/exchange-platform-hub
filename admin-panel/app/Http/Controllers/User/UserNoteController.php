<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserNoteController extends Controller
{
    public function note(User $user)
    {
        return $user->support_description;
    }

    public function updateNote(Request $request, User $user)
    {
        $request->validate([
            'support_description' => ['nullable'],
        ]);
        $user->update([
            'support_description' => $request->support_description,
        ]);

        return $user;
    }
}
