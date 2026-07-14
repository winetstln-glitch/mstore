<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
