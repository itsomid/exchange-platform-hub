<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otc_orders', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('ref_exchange_description');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('otc_orders', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->dropSoftDeletes();
        });
    }
};
