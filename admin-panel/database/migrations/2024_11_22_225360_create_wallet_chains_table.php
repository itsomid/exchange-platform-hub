<?php

use App\Models\Wallet;
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
        Schema::create('wallet_chains', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Wallet::class)->constrained()->cascadeOnDelete();
            $table->string('currency_chain');
            $table->string('address')->nullable();
            $table->timestamps();

            $table->unique(['wallet_id', 'currency_chain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_chains');
    }
};
