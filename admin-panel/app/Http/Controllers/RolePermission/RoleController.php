<?php

namespace App\Http\Controllers\RolePermission;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();

        return view('dashboard.role.index')
            ->with(['roles' => $roles]);
    }

    public function create()
    {
        $permissions = Permission::query()->get();

        return view('dashboard.role.create')
            ->with(['permissions' => $permissions]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => ['required', 'unique:roles'],
            'persian_name' => ['required','unique:roles'],
        ]);

        $input = $request->all();

        $role = Role::create([
            'name' => $input['name'],
            'persian_name' => $input['persian_name'],
        ]);

        if (isset($input['permissions'])) {
            $permissions = Permission::whereIn('id', $input['permissions'])->pluck('name')->toArray();
            foreach ($permissions as $permission) {
                $role->givePermissionTo($permission);
            }
        }
        Toast::message('نقش با موفقیت ایجاد شد')->success()->notify();
        return redirect()->route('admin.role.index');
    }

    public function edit(Role $role)
    {

        $permissions = Permission::query()->get();

        return view('dashboard.role.edit')
            ->with(['role' => $role])
            ->with(['permissions' => $permissions]);
    }

    public function update(Request $request, Role $role)
    {

        $this->validate($request, [
            'name' => ['required', 'unique:roles,name,' . $role->id],
            'persian_name' => ['required','unique:roles,persian_name,' . $role->id],
        ]);

        $input = $request->all();

        $role->update([
            'name' => $input['name'],
            'persian_name' => $input['persian_name'],
        ]);
        
        if (isset($input['permissions'])) {
            $role->syncPermissions($input['permissions']);
        } else {
            $role->syncPermissions([]); // Clear all permissions if none selected
        }
        
        Toast::message('نقش با موفقیت ویرایش شد')->success()->notify();
        return redirect()->route('admin.role.index');
    }

    public function destroy(Role $role)
    {
        // Check if the role has any users associated with it
        if ($role->users()->count() > 0) {
            Toast::message('این نقش دارای کاربران مرتبط است و نمی‌توان آن را حذف کرد')->danger()->notify();
            return redirect()->route('admin.role.index');
        }

        $role->delete();
        Toast::message('نقش با موفقیت حذف شد')->success()->notify();
        return redirect()->route('admin.role.index');
    }
}
