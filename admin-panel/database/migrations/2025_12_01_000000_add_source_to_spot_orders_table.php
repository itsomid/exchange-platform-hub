<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SpotOrderSourceEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_orders', function (Blueprint $table) {
            $table->string('source')->default(SpotOrderSourceEnum::USER->value)->after('user_id');

            $table->index('source');
            $table->index(['market_id', 'status', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('spot_orders', function (Blueprint $table) {
            $table->dropIndex(['market_id', 'status', 'source']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source']);
        });
    }
};
