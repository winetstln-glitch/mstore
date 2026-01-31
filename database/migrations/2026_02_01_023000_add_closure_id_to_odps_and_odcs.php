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
        Schema::table('odps', function (Blueprint $table) {
            if (!Schema::hasColumn('odps', 'closure_id')) {
                $table->foreignId('closure_id')->nullable()->after('odc_id')->constrained('closures')->nullOnDelete();
            }
        });

        Schema::table('odcs', function (Blueprint $table) {
            if (!Schema::hasColumn('odcs', 'closure_id')) {
                $table->foreignId('closure_id')->nullable()->after('olt_id')->constrained('closures')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            if (Schema::hasColumn('odps', 'closure_id')) {
                $table->dropForeign(['closure_id']);
                $table->dropColumn('closure_id');
            }
        });

        Schema::table('odcs', function (Blueprint $table) {
            if (Schema::hasColumn('odcs', 'closure_id')) {
                $table->dropForeign(['closure_id']);
                $table->dropColumn('closure_id');
            }
        });
    }
};
