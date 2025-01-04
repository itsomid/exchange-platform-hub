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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency_symbol');
            $table->string('currency_chain');
            $table->decimal('amount', 18, 8)->nullable();
            $table->string('address')->nullable();
            $table->string('transaction_hash', 96)->nullable()->unique();
            $table->string('status')->default('pending');
            $table->text('description')->nullable();
            $table->dateTime('expiration_date')->nullable()->default(null);
            $table->timestamp('confirmed_at')->nullable(); // Timestamp for when deposit is confirmed
            $table->timestamps();


            $table->index(['user_id', 'currency_symbol']);
            $table->index(['user_id', 'currency_symbol', 'status']);
            $table->index('user_id');
            $table->index('currency_symbol');

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
