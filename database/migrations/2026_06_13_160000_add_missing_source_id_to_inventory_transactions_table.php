<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_transactions')) {
            return;
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transactions', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_transactions') || ! Schema::hasColumn('inventory_transactions', 'source_id')) {
            return;
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn('source_id');
        });
    }
};
