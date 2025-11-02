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
        Schema::connection('api_system_db')->create('requests_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('systems')->onDelete('cascade');
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->integer('response_status');
            $table->longText('response_body')->nullable();
            $table->integer('response_time_ms')->nullable(); // Response time in milliseconds
            $table->timestamp('requested_at');
            $table->timestamps();

            $table->index(['system_id', 'requested_at']);
            $table->index(['endpoint', 'method']);
            $table->index(['response_status', 'requested_at']);
            $table->index(['ip_address', 'requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('api_system_db')->dropIfExists('requests_log');
    }
};
