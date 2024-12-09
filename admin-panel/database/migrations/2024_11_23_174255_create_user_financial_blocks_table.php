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
        Schema::create('user_financial_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Reference to the user
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('action'); // Financial status (active or restricted)
            $table->string('reason'); // reason of block (admin,group block, password change,...)
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('description')->nullable(); // Timestamp until withdrawal is restricted
            $table->timestamp('restricted_until')->nullable(); // Timestamp until withdrawal is restricted

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_financial_blocks');
    }
};
