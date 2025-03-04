<?php

use App\Models\OTCOrder;
use App\Models\SpotOrder;
use App\Models\Wallet;
use App\Models\Withdrawal;
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
        Schema::create('locked_balance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Wallet::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('amount', 18, 8);
            $table->string('type');
            $table->foreignIdFor(Withdrawal::class)->nullable();
            $table->foreignIdFor(OTCOrder::class, 'otc_order_id')->nullable();
            $table->foreignIdFor(SpotOrder::class)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locked_balance_details');
    }
};
