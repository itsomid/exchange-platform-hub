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
        // Original users table already creates `national_code`; only rename if the legacy
        // `national_id` column still exists (older environments).
        if (Schema::hasColumn('users', 'national_id') && ! Schema::hasColumn('users', 'national_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('national_id', 'national_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'national_code') && ! Schema::hasColumn('users', 'national_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('national_code', 'national_id');
            });
        }
    }
};
