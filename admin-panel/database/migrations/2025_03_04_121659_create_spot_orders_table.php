<?php

use App\Models\Market;
use App\Models\User;
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
        Schema::create('spot_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Market::class)->constrained();
            $table->string('order_type'); //buy, sell
            $table->string('order_kind'); //market, limit
            $table->decimal('quantity', 18, 8);
            $table->decimal('price', 18, 8);
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('sport_trade', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('buy_order_id');
            $table->unsignedInteger('sell_order_id');

            $table->decimal('quantity', 18, 8);
            $table->decimal('price', 18, 8);

            $table->unique(['buy_order_id', 'sell_order_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_orders');
    }
};
