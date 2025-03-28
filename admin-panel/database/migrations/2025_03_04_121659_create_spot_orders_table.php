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
            $table->string('side'); //buy, sell
            $table->string('type'); //market, limit
            $table->decimal('quantity', 18, 8);
            $table->decimal('price', 18, 8)->nullable();
            $table->string('status');
            $table->decimal('filled_quantity', 18, 8)->default(0); // How much has been filled
            $table->timestamps();
        });

        Schema::create('spot_trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('maker_order_id');
            $table->unsignedInteger('taker_order_id');
            $table->foreignIdFor(Market::class)->constrained();

            $table->decimal('quantity', 18, 8);
            $table->decimal('price', 18, 8);

            $table->unique(['maker_order_id', 'taker_order_id']);
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
