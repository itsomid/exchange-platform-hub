<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_signals', function (Blueprint $table) {
            // Set to now() when an admin (re)activates a signal; the api-service
            // scan command consumes it (runs a buy round) then clears it to null.
            $table->timestamp('activation_pending_at')->nullable()->after('is_active');
            // Last-known "live price within [floor, ceiling]" snapshot, used by the
            // scan command to detect an out-of-range -> in-range price transition.
            $table->boolean('price_in_range')->nullable()->after('activation_pending_at');
        });
    }

    public function down(): void
    {
        Schema::table('bot_signals', function (Blueprint $table) {
            $table->dropColumn(['activation_pending_at', 'price_in_range']);
        });
    }
};
