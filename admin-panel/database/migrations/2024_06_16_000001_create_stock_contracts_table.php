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
        Schema::create('stock_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->string('contract_number')->unique();
            $table->string('contract_file')->nullable();
            $table->decimal('amount', 18, 8);
            $table->decimal('total_value', 18, 2);
            $table->enum('contract_status', ['active', 'cancelled', 'sold'])->default('active');
            $table->decimal('cancellation_fee', 18, 2)->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_contracts');
    }
};
