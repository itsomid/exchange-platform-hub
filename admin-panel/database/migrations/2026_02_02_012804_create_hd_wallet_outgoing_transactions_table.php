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
        Schema::create('hd_wallet_outgoing_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('HD Wallet Index = User ID');
            $table->string('currency_symbol', 20)->index();
            $table->unsignedBigInteger('currency_chain_id')->index();
            $table->decimal('amount', 36, 18)->comment('Amount sent out');
            $table->string('transaction_hash', 100)->unique();
            $table->string('from_address', 100)->index()->comment('HD Wallet address');
            $table->string('to_address', 100)->nullable()->comment('Destination address');
            $table->unsignedBigInteger('block_number')->nullable();
            $table->timestamp('transaction_at')->nullable()->comment('Block timestamp');
            $table->enum('source', ['sync', 'webhook', 'manual'])->default('sync')->comment('How this record was created');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('currency_chain_id')->references('id')->on('currency_chains')->onDelete('cascade');
            
            $table->index(['user_id', 'currency_symbol', 'currency_chain_id'], 'idx_user_currency_chain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_wallet_outgoing_transactions');
    }
};
