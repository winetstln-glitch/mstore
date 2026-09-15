<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 255)->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'scope_config')) {
                $table->json('scope_config')->nullable()->after('company_branch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'scope_config')) {
                $table->dropColumn('scope_config');
            }
            if (Schema::hasColumn('users', 'job_title')) {
                $table->dropColumn('job_title');
            }
        });
    }
};
