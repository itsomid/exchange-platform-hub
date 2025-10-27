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
        Schema::create('spot_bot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('price_interval_seconds')->default(5);
            $table->decimal('order_margin', 5, 4)->default(0.1);
            $table->unsignedInteger('buy_orders_count')->default(5);
            $table->unsignedInteger('sell_orders_count')->default(5);
            $table->foreignId('fake_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('market_crash_percentage', 5, 2)->nullable();
            $table->decimal('min_order_size', 18, 8)->nullable();
            $table->decimal('max_order_size', 18, 8)->nullable();
            $table->timestamps();

            $table->unique('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_bot_settings');
    }
};
