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
        Schema::table('currency_chains', function (Blueprint $table) {
            $table->string('contract_address')->nullable()->after('explorer_tx_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currency_chains', function (Blueprint $table) {
            //
        });
    }
};
