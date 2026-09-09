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
        Schema::create('sweeper_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sweeper_id', 64)->unique()->comment('MongoDB _id from sweeper transaction_logs');
            $table->string('tx_hash', 100)->nullable()->index();
            $table->string('network', 50)->index();
            $table->string('symbol', 20)->index();
            $table->string('wallet_id', 100)->nullable()->index();
            $table->unsignedInteger('address_index')->nullable();
            $table->string('type', 30)->nullable()->index()->comment('sweep|consolidation|withdrawal|deposit');
            $table->string('coin_type', 20)->nullable()->comment('native|token');
            $table->string('from_address', 100)->nullable()->index();
            $table->string('to_address', 100)->nullable()->index();
            $table->string('derivation_path', 100)->nullable();
            $table->string('amount', 78)->nullable();
            $table->string('amount_in_wei', 78)->nullable();
            $table->string('amount_in_satoshi', 78)->nullable();
            $table->string('fee', 78)->nullable();
            $table->string('fee_in_wei', 78)->nullable();
            $table->string('fee_in_satoshi', 78)->nullable();
            $table->string('gas_used', 78)->nullable();
            $table->string('gas_price', 78)->nullable();
            $table->string('status', 30)->default('broadcasted')->index();
            $table->unsignedInteger('confirmations')->default(0);
            $table->unsignedInteger('required_confirmations')->nullable();
            $table->unsignedBigInteger('block_number')->nullable()->index();
            $table->string('block_hash', 100)->nullable();
            $table->timestamp('broadcast_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('sweeper_created_at')->nullable();
            $table->timestamp('sweeper_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->json('raw_payload')->nullable()->comment('Full sweeper transaction log document');
            $table->timestamps();

            $table->index(['status', 'network', 'symbol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sweeper_transaction_logs');
    }
};
