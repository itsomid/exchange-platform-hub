<?php

use App\Models\Market;
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
        Schema::create('market_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Market::class)->constrained();
            $table->decimal('open', 16, 8);
            $table->decimal('high', 16, 8);
            $table->decimal('low', 16, 8);
            $table->decimal('close', 16, 8);
            $table->decimal('volume', 16, 8);
            $table->timestamp('timestamp');

            $table->unique(['market_id', 'timestamp']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_histories');
    }
};
