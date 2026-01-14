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
        Schema::table('olts', function (Blueprint $table) {
            if (!Schema::hasColumn('olts', 'type')) {
                $table->enum('type', ['epon', 'gpon', 'xpon'])->default('epon');
            }
            if (!Schema::hasColumn('olts', 'brand')) {
                $table->string('brand')->default('zte');
            }
            if (!Schema::hasColumn('olts', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('olts', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('olts', 'port')) {
                $table->integer('port')->default(23);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            if (Schema::hasColumn('olts', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('olts', 'brand')) {
                $table->dropColumn('brand');
            }
            if (Schema::hasColumn('olts', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('olts', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('olts', 'port')) {
                $table->dropColumn('port');
            }
        });
    }
};
