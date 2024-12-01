<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = false;
        $pivotRole = 'role_id';
        $pivotPermission = 'permission_id';

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id'); // permission id
            $table->string('name');       // For MySQL 8.0 use string('name', 125);
            $table->string('persian_name')->nullable();
            $table->string('description')->nullable();
            $table->string('guard_name'); // For MySQL 8.0 use string('guard_name', 125);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) use ($teams) {
            $table->bigIncrements('id'); // role id
            if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger('team_id')->nullable();
                $table->index('team_id', 'roles_team_foreign_key_index');
            }
            $table->string('name');       // For MySQL 8.0 use string('name', 125);
            $table->string('persian_name')->nullable();
            $table->string('guard_name'); // For MySQL 8.0 use string('guard_name', 125);
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique(['team_id', 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create('model_has_permissions', function (Blueprint $table) use ($pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on('permissions')
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger('team_id');
                $table->index('team_id', 'model_has_permissions_team_foreign_key_index');

                $table->primary(['team_id', $pivotPermission, 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }

        });

        Schema::create('model_has_roles', function (Blueprint $table) use ($pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on('roles')
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger('team_id');
                $table->index('team_id', 'model_has_roles_team_foreign_key_index');

                $table->primary(['team_id', $pivotRole, 'model_id', 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, 'model_id', 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create('role_has_permissions', function (Blueprint $table) use ($pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on('permissions')
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on('roles')
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('role_has_permissions');
        Schema::drop('model_has_roles');
        Schema::drop('model_has_permissions');
        Schema::drop('roles');
        Schema::drop('permissions');
    }
};
