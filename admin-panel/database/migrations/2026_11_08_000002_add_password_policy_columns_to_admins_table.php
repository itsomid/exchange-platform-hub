<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            // Last time the admin changed their own password (null = never, so a change is required).
            $table->timestamp('password_changed_at')->nullable()->after('password');
            // End of the grace period; set when the admin is first told to change the password.
            $table->timestamp('password_change_deadline_at')->nullable()->after('password_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['password_changed_at', 'password_change_deadline_at']);
        });
    }
};
