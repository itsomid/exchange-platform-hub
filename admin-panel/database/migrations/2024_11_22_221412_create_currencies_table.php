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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); //Tether
            $table->string('symbol')->unique(); //USDT
            $table->string('logo')->nullable();
//            $table->boolean('deposit_enabled')->default(true); // Is deposit enabled for this currency
//            $table->boolean('withdraw_enabled')->default(true); // Is withdrawal enabled for this currency
            $table->boolean('inter_transfer_enabled')->default(true); // Is internal transfer enabled
            $table->decimal('max_auto_withdraw_amount', 18, 8)->default(0); // Maximum withdrawal amount without admin approval
            $table->unsignedTinyInteger('precision')->default(8);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
