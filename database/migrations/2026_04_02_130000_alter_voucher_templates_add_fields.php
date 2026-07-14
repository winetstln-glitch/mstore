<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('voucher_templates', 'name')) {
                $table->string('name')->after('id');
            }
            if (! Schema::hasColumn('voucher_templates', 'rate_limit')) {
                $table->string('rate_limit')->nullable()->after('name');
            }
            if (! Schema::hasColumn('voucher_templates', 'duration_seconds')) {
                $table->unsignedBigInteger('duration_seconds')->nullable()->after('rate_limit');
            }
            if (! Schema::hasColumn('voucher_templates', 'quota_mb')) {
                $table->unsignedBigInteger('quota_mb')->nullable()->after('duration_seconds');
            }
            if (! Schema::hasColumn('voucher_templates', 'price')) {
                $table->decimal('price', 14, 2)->default(0)->after('quota_mb');
            }
            if (! Schema::hasColumn('voucher_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voucher_templates', function (Blueprint $table) {
            foreach (['name', 'rate_limit', 'duration_seconds', 'quota_mb', 'price', 'is_active'] as $col) {
                if (Schema::hasColumn('voucher_templates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
