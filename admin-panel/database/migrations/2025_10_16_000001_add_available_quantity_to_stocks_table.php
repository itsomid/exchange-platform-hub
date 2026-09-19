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
        // Columns were later back-filled into create_stocks_table, so a fresh
        // migrate (e.g. sqlite :memory: in tests) already has them.
        Schema::table('stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('stocks', 'initial_quantity')) {
                $table->decimal('initial_quantity', 18, 3)->default(0)->after('value')->comment('تعداد کل سهام اولیه');
            }
            if (! Schema::hasColumn('stocks', 'available_quantity')) {
                $table->decimal('available_quantity', 18, 3)->default(0)->after('initial_quantity')->comment('تعداد سهام موجود');
            }
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
