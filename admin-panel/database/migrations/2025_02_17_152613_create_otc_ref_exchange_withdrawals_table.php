<?php

use App\Models\Currency;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\Transaction;
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
        Schema::create('otc_ref_exchange_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Currency::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Transaction::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(ExchangeAssetsWithdrawal::class)->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otc_ref_exchange_withdrawals');
    }
};
