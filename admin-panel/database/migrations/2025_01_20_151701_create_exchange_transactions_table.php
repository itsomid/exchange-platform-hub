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
        Schema::create('exchange_transactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('orderable');
            $table->unsignedBigInteger('order_id');
            $table->string('market');
            $table->string('currency_symbol')->nullable();
            $table->decimal('amount', 18, 8);
            $table->decimal('fee', 18, 8);
            $table->decimal('filled_amount', 18, 8);
            $table->string('side');
            $table->json('response');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_transactions');
    }
};
