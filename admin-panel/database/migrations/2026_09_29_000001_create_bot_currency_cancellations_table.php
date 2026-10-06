<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per admin "cancel this coin across every bot order" run. The run is
 * executed by a queued job in api-service; this row carries the admin's
 * choices, the reason shown on every affected buy execution, live progress
 * and the final totals. Every sell tier settled by the run points back here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_currency_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('admin_label')->nullable();

            $table->boolean('cancel_on_exchange')->default(false);
            $table->boolean('sell_on_exchange')->default(false);
            $table->text('reason');

            $table->string('status', 16)->default('PENDING'); // PENDING|RUNNING|DONE|FAILED

            // Price every tier was settled at: the aggregated market-sell fill
            // price (market_sell) or the live feed price (live).
            $table->string('price_source', 16)->nullable();
            $table->decimal('settlement_price', 30, 8)->nullable();

            // Aggregated market sell on the reference exchange (sell_on_exchange only).
            $table->string('sell_exchange_order_id')->nullable();
            $table->decimal('sold_amount', 30, 8)->default(0);
            $table->decimal('sell_exchange_fee', 20, 8)->default(0);
            $table->string('sell_exchange_fee_currency', 16)->nullable();
            $table->text('sell_error')->nullable();

            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('sell_orders_count')->default(0);
            // Tiers left untouched: already filled on the exchange, or the
            // exchange-side cancel failed (they stay OPEN for the normal sync).
            $table->unsignedInteger('skipped_count')->default(0);

            $table->decimal('total_amount', 30, 8)->default(0);
            $table->decimal('total_principal', 20, 8)->default(0);
            $table->decimal('total_performance_fee', 20, 8)->default(0);
            $table->decimal('total_refund', 20, 8)->default(0);

            $table->json('details')->nullable();
            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['currency_id', 'status']);
        });

        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->foreignId('bot_currency_cancellation_id')
                ->nullable()
                ->after('cancel_reason')
                ->constrained('bot_currency_cancellations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bot_currency_cancellation_id');
        });

        Schema::dropIfExists('bot_currency_cancellations');
    }
};
