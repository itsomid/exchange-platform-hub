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
        Schema::create('referral_code_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->constrained()->onDelete('cascade'); // Links to the referral code
            $table->foreignId('used_by')->constrained(table: 'users')->onDelete('cascade'); // The friend who used the code
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['introducer', 'friend'])->default('friend');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_code_usages');
    }
};
