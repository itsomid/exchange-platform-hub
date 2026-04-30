<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->boolean('show_in_home')->default(false)->after('ref_exchange_sell_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn('show_in_home');
        });
    }
};
