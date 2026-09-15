<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_system')->default(true)->after('label');
            });
        }

        if (! Schema::hasColumn('users', 'job_title')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('job_title')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('users', 'scope_config')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('scope_config')->nullable()->after('company_branch_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'scope_config')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('scope_config');
            });
        }

        if (Schema::hasColumn('users', 'job_title')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('job_title');
            });
        }

        if (Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
