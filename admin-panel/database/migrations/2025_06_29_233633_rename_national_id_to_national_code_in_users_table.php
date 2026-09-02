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
        // On a fresh database the users table is already created with
        // national_code, so only rename when the legacy column is present.
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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('national_code', 'national_id');
        });
    }
};
