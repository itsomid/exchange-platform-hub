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
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('coin_price', 20, 8)->nullable()->after('balance'); // قیمت ارز به دلار
            $table->unsignedBigInteger('price_source_id')->nullable()->after('coin_price'); // Reference to exchanges table
            $table->foreign('price_source_id')->references('id')->on('exchanges')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['price_source_id']);
            $table->dropColumn('price_source_id');
            $table->dropColumn('coin_price');
        });
    }
};