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
        Schema::table('referral_code_usages', function (Blueprint $table) {
            $table->enum('type', ['introducer', 'friend'])->default('friend')->after('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_code_usages', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
