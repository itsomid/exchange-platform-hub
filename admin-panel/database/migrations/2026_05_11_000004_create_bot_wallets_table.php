<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 20, 8)->default(0);
            $table->decimal('principal_balance', 20, 8)->default(0); // stored for future reinvest (D1)
            $table->decimal('profit_balance', 20, 8)->default(0);    // stored for future reinvest (D1)
            $table->decimal('locked_balance', 20, 8)->default(0);    // in-flight buys + open sells
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_wallets');
    }
};
