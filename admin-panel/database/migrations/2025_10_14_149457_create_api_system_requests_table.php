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
        Schema::connection('api_system_db')->create('api_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('system_id')->constrained('systems')->onDelete('cascade');
            $table->unsignedBigInteger('user_id'); // User ID from external system
            $table->string('type', 50); // Request type - flexible string instead of enum
            $table->string('tracking_code', 100)->nullable(); // Tracking code for grouping related requests

            // Polymorphic relationship fields
            $table->string('model_type')->nullable(); // Model class name (e.g., App\Models\Transaction)
            $table->unsignedBigInteger('model_id')->nullable(); // ID of the related model

            // Common fields for all request types
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->json('user_data')->nullable(); // Additional user information from external system
            $table->timestamp('processed_at')->nullable();

            // Flexible data storage for different request types
            $table->json('request_data')->nullable(); // Specific data for each request type
            $table->json('response_data')->nullable(); // Response data after processing

            // Reference ID is sufficient to link to specific records in other tables

            $table->timestamps();

            // Indexes for better performance
            $table->index(['system_id', 'type']);
            $table->index(['user_id', 'type']);
            $table->index(['status', 'processed_at']);
            $table->index(['type', 'created_at']);
            $table->index(['model_type', 'model_id']); // Index for polymorphic relationship
            $table->index(['tracking_code']); // Index for tracking code queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('api_system_db')->dropIfExists('api_requests');
    }
};
