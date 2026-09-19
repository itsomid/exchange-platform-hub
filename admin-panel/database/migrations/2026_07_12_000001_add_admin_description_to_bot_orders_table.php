<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bot_orders', 'admin_description')) {
            Schema::table('bot_orders', function (Blueprint $table) {
                $table->text('admin_description')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bot_orders', 'admin_description')) {
            Schema::table('bot_orders', function (Blueprint $table) {
                $table->dropColumn('admin_description');
            });
        }
    }
};
