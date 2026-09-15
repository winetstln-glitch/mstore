<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('olts', 'region_id')) {
            Schema::table('olts', function (Blueprint $table) {
                $table->foreignId('region_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('olts', 'company_id')) {
            Schema::table('olts', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('routers', 'region_id')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->foreignId('region_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('routers', 'company_id')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('routers', 'company_id')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasColumn('routers', 'region_id')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->dropForeign(['region_id']);
                $table->dropColumn('region_id');
            });
        }

        if (Schema::hasColumn('olts', 'company_id')) {
            Schema::table('olts', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasColumn('olts', 'region_id')) {
            Schema::table('olts', function (Blueprint $table) {
                $table->dropForeign(['region_id']);
                $table->dropColumn('region_id');
            });
        }
    }
};
