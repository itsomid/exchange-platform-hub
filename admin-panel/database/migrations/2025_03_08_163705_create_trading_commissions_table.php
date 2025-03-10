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
            $table->decimal('commission_amount', 18, 8);
            $table->string('commission_percentage');
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
