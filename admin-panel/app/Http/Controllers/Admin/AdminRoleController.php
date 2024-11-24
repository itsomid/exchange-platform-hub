<?php

namespace App\Http\Controllers\Admin;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminRoleController extends Controller
{
    public function edit(Admin $admin)
    {
        $roles = Role::query()->get();

        return view('dashboard.admin.role.edit')
            ->with(['user' => $admin])
            ->with(['roles' => $roles]);
    }

    public function update(Admin $admin, Request $request)
    {
        $admin->roles()->sync($request->roles);
        Toast::message('نقش کاربر با موفقیت ویرایش شد.')->success()->notify();

        return redirect()->back();
    }
}
