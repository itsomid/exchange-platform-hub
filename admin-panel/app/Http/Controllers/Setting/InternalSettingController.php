<?php

namespace App\Http\Controllers\Setting;

use App\Data\PermissionList;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class InternalSettingController extends Controller
{
    public function index()
    {
        $last3permissions = Permission::query()->latest()->take(3)->get();

        return view('dashboard.setting.internal.index')
            ->with(['last3permissions' => $last3permissions]);
    }

    public function updatePermissions()
    {
        $permissions = [];
        $currentPermissions = Permission::all();

        foreach (PermissionList::get() as $permission) {
            if (! $currentPermissions->where('name', $permission[0])->first()) {
                $permissions[] = Permission::Create(['name' => $permission[0], 'persian_name' => $permission[1]]);
            }
        }

        Toast::message('دستریسی های جدید افزوده شدند')->success()->notify();

        return redirect()->route('admin.internal.setting.index');
    }
}
