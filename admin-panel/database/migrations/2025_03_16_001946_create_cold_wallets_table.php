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
        Schema::create('cold_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('public_key', 255)->unique();
            $table->timestamps();
        });

        // Create a trigger to prevent updates to the public_key column
        DB::unprepared('
            CREATE TRIGGER prevent_cold_wallet_update
            BEFORE UPDATE ON cold_wallets
            FOR EACH ROW
            SIGNAL SQLSTATE "45000"
            SET MESSAGE_TEXT = "Updating cold wallet public key is not allowed";
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cold_wallets');
    }
};
