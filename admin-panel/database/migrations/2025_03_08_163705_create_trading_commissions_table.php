<?php

use App\Models\SpotTrade;
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
        Schema::create('trading_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(SpotTrade::class)->constrained();
            $table->decimal('maker_commission_amount', 18, 8);
            $table->string('maker_commission_percentage');
            $table->string('maker_commission_currency');
            $table->decimal('taker_commission_amount', 18, 8);
            $table->string('taker_commission_percentage');
            $table->string('taker_commission_currency');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_commissions');
    }
};
