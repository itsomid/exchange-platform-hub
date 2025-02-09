<?php

use App\Models\Admin;
use App\Models\User;
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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('subject');
            $table->string('status')->default('open');
            $table->string('priority')->default('low');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->morphs('ticketable');

            $table->timestamps();
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->morphs('repliable'); // User or Admin
            $table->string('image')->nullable();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_private')->default(false);
            $table->boolean('is_seen')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
