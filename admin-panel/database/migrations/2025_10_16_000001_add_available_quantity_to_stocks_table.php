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
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('initial_quantity', 18, 3)->default(0)->after('value')->comment('تعداد کل سهام اولیه');
            $table->decimal('available_quantity', 18, 3)->default(0)->after('initial_quantity')->comment('تعداد سهام موجود');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['initial_quantity', 'available_quantity']);
        });
    }
};
