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
        Schema::table('exchange_transactions', function (Blueprint $table) {
            $table->foreignId('exchange_id')->nullable()->after('orderable_id')->constrained('exchanges')->onDelete('cascade');
            $table->string('order_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_transactions', function (Blueprint $table) {
            $table->dropForeign(['exchange_id']);
            $table->dropColumn('exchange_id');
            $table->unsignedBigInteger('order_id')->change();
        });
    }
};
