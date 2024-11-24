<?php

namespace App\Http\Controllers\RolePermission;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::query()->get();

        return view('dashboard.permission.index')->with(['permissions' => $permissions]);
    }

    public function edit(Permission $permission)
    {
        return view('dashboard.permission.edit')->with(['permission' => $permission]);
    }

    public function update(Request $request, Permission $permission)
    {
        $this->validate($request, [
            'persian_name' => ['required', 'max:191'],
            'description' => ['nullable', 'max:191'],
        ]);

        $permission->update([
            'persian_name' => $request->persian_name,
            'description' => $request->description,
        ]);

        Toast::message('ویرایش توضیحات مجور با موفقیت انجام شد')->success()->notify();

        return redirect()->back();
    }
}
