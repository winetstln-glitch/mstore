<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'ai_summary')) {
                $table->text('ai_summary')->nullable()->after('description');
            }
            if (! Schema::hasColumn('tickets', 'ai_category')) {
                $table->string('ai_category', 30)->nullable()->after('ai_summary')->index();
            }
            if (! Schema::hasColumn('tickets', 'ai_confidence')) {
                $table->decimal('ai_confidence', 5, 2)->nullable()->after('ai_category');
            }
            if (! Schema::hasColumn('tickets', 'sla_status')) {
                $table->string('sla_status', 20)->nullable()->after('sla_deadline')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'sla_status')) {
                $table->dropColumn('sla_status');
            }
            if (Schema::hasColumn('tickets', 'ai_confidence')) {
                $table->dropColumn('ai_confidence');
            }
            if (Schema::hasColumn('tickets', 'ai_category')) {
                $table->dropColumn('ai_category');
            }
            if (Schema::hasColumn('tickets', 'ai_summary')) {
                $table->dropColumn('ai_summary');
            }
        });
    }
};
