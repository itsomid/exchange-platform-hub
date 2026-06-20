<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_trades', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('taker_order_id');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('spot_trades', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->dropSoftDeletes();
        });
    }
};
