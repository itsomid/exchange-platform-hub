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
        Schema::table('sweeper_transaction_logs', function (Blueprint $table) {
            $table->foreignId('withdrawal_transaction_id')
                ->nullable()
                ->after('raw_payload')
                ->constrained('transactions')
                ->nullOnDelete();
            $table->foreignId('fee_transaction_id')
                ->nullable()
                ->after('withdrawal_transaction_id')
                ->constrained('transactions')
                ->nullOnDelete();
            $table->timestamp('transactions_created_at')
                ->nullable()
                ->after('fee_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sweeper_transaction_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_transaction_id');
            $table->dropConstrainedForeignId('fee_transaction_id');
            $table->dropColumn('transactions_created_at');
        });
    }
};
