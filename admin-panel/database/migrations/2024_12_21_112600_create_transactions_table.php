<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('id'); // Primary key
            $table->unsignedBigInteger('user_id'); // Foreign key
            $table->unsignedBigInteger('wallet_id'); // Foreign key
            $table->unsignedBigInteger('deposit_id')->nullable(); // Foreign key
            $table->unsignedBigInteger('withdrawal_id')->nullable(); // Foreign key
//            $table->unsignedBigInteger('otc_order_id')->nullable(); // Foreign key
//            $table->unsignedBigInteger('spot_order_id')->nullable(); // Foreign key
            $table->decimal('amount', 20, 8); // To handle precise values like cryptocurrency
            $table->decimal('balance', 18, 8); // balance after transaction
            $table->string('type', 50); // Type of transaction
            $table->string('status', 50); // Status column

            $table->text('description')->nullable(); // Optional description
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->foreign('deposit_id')->references('id')->on('deposits')->onDelete('set null');
            $table->foreign('withdrawal_id')->references('id')->on('withdrawals')->onDelete('set null');
//            $table->foreign('otc_order_id')->references('id')->on('otc_orders')->onDelete('set null');
//            $table->foreign('spot_order_id')->references('id')->on('spot_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
