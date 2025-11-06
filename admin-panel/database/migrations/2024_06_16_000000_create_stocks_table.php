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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('value', 18, 2);
             $table->decimal('initial_quantity', 18, 3)->default(0);
            $table->decimal('available_quantity', 18, 3)->default(0);
            $table->enum('type', ['normal', 'gift', 'partner']);
            $table->decimal('cancellation_fee',  5, 2)->comment('Percentage value (0-100)')->default(0);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
