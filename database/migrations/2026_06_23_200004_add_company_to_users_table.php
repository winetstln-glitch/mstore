<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('users', 'company_branch_id')) {
                $table->foreignId('company_branch_id')->nullable()->constrained('company_branches')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'company_branch_id')) {
                $table->dropForeign(['company_branch_id']);
                $table->dropColumn('company_branch_id');
            }
            if (Schema::hasColumn('users', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};