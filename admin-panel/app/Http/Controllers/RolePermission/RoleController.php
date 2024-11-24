<?php

namespace App\Http\Controllers\RolePermission;

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
            'persian_name' => ['required'],
        ]);

        $input = $request->all();

        $role = Role::create([
            'name' => $input['name'],
            'persian_name' => $input['persian_name'],
        ]);

        if (isset($input['permissions'])) {
            $role->syncPermissions($input['permissions']);
        }

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
            'name' => ['required'],
            'persian_name' => ['required'],
        ]);
        $input = $request->all();

        $role->update([
            'name' => $input['name'],
            'persian_name' => $input['persian_name'],
        ]);
        $role->syncPermissions($input['permissions']);

        return redirect()->route('admin.role.index');
    }
}
