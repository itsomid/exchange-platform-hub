<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ایجاد permission جدید برای مدیریت کانتینرهای Sweeper
        $permission = Permission::create([
            'name' => 'sweeper.management',
            'persian_name' => 'مدیریت کانتینرهای Sweeper',
        ]);

        // اعطای مجوز به نقش‌های admin و super_admin
        $roleAdmin = Role::where('name', 'admin')->first();
        if ($roleAdmin) {
            $roleAdmin->givePermissionTo('sweeper.management');
        }

        $roleSuperAdmin = Role::where('name', 'super_admin')->first();
        if ($roleSuperAdmin) {
            $roleSuperAdmin->givePermissionTo('sweeper.management');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف مجوز از نقش‌ها
        $roleAdmin = Role::where('name', 'admin')->first();
        if ($roleAdmin) {
            $roleAdmin->revokePermissionTo('sweeper.management');
        }

        $roleSuperAdmin = Role::where('name', 'super_admin')->first();
        if ($roleSuperAdmin) {
            $roleSuperAdmin->revokePermissionTo('sweeper.management');
        }

        // حذف permission
        $permission = Permission::where('name', 'sweeper.management')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
