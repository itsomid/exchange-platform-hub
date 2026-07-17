<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_trade_settlements', function (Blueprint $table) {
            $table->decimal('referral_fee', 20, 8)->default(0)->after('performance_fee')
                ->comment('Portion of the performance fee paid to the settling user introducer.');
            $table->foreignId('referral_user_id')->nullable()->after('referral_fee')
                ->comment('The introducer (referrer) who received the referral commission for this settlement.')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bot_trade_settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_user_id');
            $table->dropColumn('referral_fee');
        });
    }
};
