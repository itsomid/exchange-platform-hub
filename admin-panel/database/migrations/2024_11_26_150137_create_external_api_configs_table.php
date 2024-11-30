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
        Schema::create('external_api_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // API Name
            $table->string('key'); // API Key
            $table->enum('status', ['active', 'inactive'])->default('active'); // Status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_api_configs');
    }
};
