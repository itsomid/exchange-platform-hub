<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per BotBuyOrchestrator invocation — every attempt to put a user's
 * free bot balance to work, whether it ended in an order or not.
 *
 * Without this, a trigger that bought nothing left no trace but a log line, so
 * a free balance sitting idle for days had no explanation attached to it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_buy_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('bot_order_id')->nullable();
            // The sell tier whose fill freed the balance (REINVEST attempts only).
            $table->unsignedBigInteger('bot_sell_order_id')->nullable();

            $table->string('triggered_by', 32);
            $table->string('outcome', 16);              // ORDER_CREATED|BLOCKED|EXCEPTION
            $table->string('reason_code', 64)->nullable();
            $table->text('reason_message')->nullable();

            $table->decimal('balance', 20, 8)->default(0);
            $table->decimal('locked_balance', 20, 8)->default(0);
            $table->decimal('free_balance', 20, 8)->default(0);
            $table->decimal('gate_amount', 20, 8)->nullable();
            $table->string('gate_kind', 16)->nullable(); // buy_floor|min_deposit
            $table->decimal('total_allocated', 20, 8)->default(0);

            $table->unsignedSmallInteger('allocated_count')->default(0);
            $table->unsignedSmallInteger('skipped_count')->default(0);
            $table->unsignedSmallInteger('out_of_range_count')->default(0);
            $table->unsignedSmallInteger('unpriced_count')->default(0);

            // Full per-currency breakdown: allocations, skips with their reason,
            // out-of-range prices vs. their window, unpriced signals.
            $table->json('details')->nullable();
            $table->text('exception')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['outcome', 'reason_code']);

            $table->foreign('bot_order_id')->references('id')->on('bot_orders')->nullOnDelete();
            $table->foreign('bot_sell_order_id')->references('id')->on('bot_sell_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_buy_attempts');
    }
};
