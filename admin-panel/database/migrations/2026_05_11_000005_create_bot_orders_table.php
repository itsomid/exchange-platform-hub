<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('batch_uuid')->unique();
            $table->decimal('total_amount_usdt', 20, 8);
            $table->decimal('alpha_snapshot', 4, 2);
            $table->string('status')->default('PENDING'); // PENDING|PARTIALLY_FILLED|FILLED|CANCELED
            $table->string('triggered_by');               // TRANSFER_IN|TOGGLE_ON|MANUAL
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_orders');
    }
};
