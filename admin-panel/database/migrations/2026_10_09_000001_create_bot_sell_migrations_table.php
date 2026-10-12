<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reference-exchange switch (CoinEx → Binance): every bot sell tier now records
 * which exchange its `exchange_order_id` belongs to. All rows that exist when
 * this runs were placed on CoinEx. An admin "sell migration" run re-places a
 * coin's OPEN CoinEx tiers on Binance by editing the same rows in place; one
 * bot_sell_migrations row tracks each run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->string('exchange', 20)->nullable()->after('exchange_order_id')->index();
            $table->unsignedBigInteger('previous_exchange_order_id')->nullable()->after('exchange');
            $table->timestamp('migrated_at')->nullable()->after('filled_at');
        });

        DB::table('bot_sell_orders')->update(['exchange' => 'coinex']);

        Schema::create('bot_sell_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('admin_label')->nullable();

            $table->string('status', 16)->default('PENDING'); // PENDING|RUNNING|DONE|FAILED

            $table->unsignedInteger('tiers_count')->default(0);
            $table->unsignedInteger('placed_count')->default(0);    // re-placed as LIMIT SELL
            $table->unsignedInteger('completed_count')->default(0); // target already hit → market-sold
            $table->unsignedInteger('failed_count')->default(0);    // left untouched, retry with another run
            $table->decimal('required_amount', 30, 8)->default(0);

            $table->json('details')->nullable();
            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['currency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_sell_migrations');

        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->dropIndex(['exchange']);
            $table->dropColumn(['exchange', 'previous_exchange_order_id', 'migrated_at']);
        });
    }
};
