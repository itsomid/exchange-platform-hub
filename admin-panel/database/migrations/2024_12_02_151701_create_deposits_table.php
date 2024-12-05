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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('currency_chain');
            $table->string('currency_symbol');
            $table->decimal('amount', 8, 2)->nullable();
            $table->string('public_key');
            $table->string('status');
            $table->dateTime('expiration_date')->nullable()->default(null);

            $table->index(['user_id', 'currency_symbol']);
            $table->index(['user_id', 'currency_symbol', 'status']);
            $table->index('user_id');
            $table->index('currency_symbol');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
