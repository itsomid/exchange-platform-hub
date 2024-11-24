<?php

namespace Database\Seeders;

use App\Data\PermissionList;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        foreach (PermissionList::get() as $permission) {
            Permission::create([
                'name' => $permission[0],
                'persian_name' => $permission[1],
            ]);
        }

        $role_admin = Role::query()->where('name', 'admin')->first();
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            $role_admin->givePermissionTo($permission->name);
        }

        $role_super_admin = Role::query()->where('name', 'super_admin')->first();
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            $role_super_admin->givePermissionTo($permission->name);
        }

        $role_support = Role::query()->where('name', 'support')->first();
        $role_support->givePermissionTo(['user.index', 'user.edit']);

    }
}
