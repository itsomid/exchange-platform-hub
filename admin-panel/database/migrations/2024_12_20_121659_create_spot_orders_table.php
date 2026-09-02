<?php

use App\Enums\SpotOrderSourceEnum;
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
            $table->string('source')->default(SpotOrderSourceEnum::USER->value);
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
            $table->unsignedBigInteger('maker_order_id');
            $table->unsignedBigInteger('taker_order_id');
            $table->foreignIdFor(Market::class)->constrained();

            $table->decimal('quantity', 18, 8);
            $table->decimal('price', 18, 8);

            $table->foreign('maker_order_id')->references('id')->on('spot_orders')->onDelete('cascade');
            $table->foreign('taker_order_id')->references('id')->on('spot_orders')->onDelete('cascade');

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
