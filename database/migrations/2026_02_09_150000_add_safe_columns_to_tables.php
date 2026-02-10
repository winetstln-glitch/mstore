<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations safely by checking if columns/tables exist
     */
    public function up(): void
    {
        // Check and add image column to atk_products
        if (Schema::hasTable('atk_products') && !Schema::hasColumn('atk_products', 'image')) {
            Schema::table('atk_products', function (Blueprint $table) {
                $table->string('image')->nullable()->after('name');
            });
        }

        // Check and add image column to wash_services
        if (Schema::hasTable('wash_services') && !Schema::hasColumn('wash_services', 'image')) {
            Schema::table('wash_services', function (Blueprint $table) {
                $table->string('image')->nullable()->after('name');
            });
        }

        // Check and add employee_id column to atk_transactions
        if (Schema::hasTable('atk_transactions') && !Schema::hasColumn('atk_transactions', 'employee_id')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
            });
        }

        // Check and add employee_id column to wash_transactions
        if (Schema::hasTable('wash_transactions') && !Schema::hasColumn('wash_transactions', 'employee_id')) {
            Schema::table('wash_transactions', function (Blueprint $table) {
                $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations safely
     */
    public function down(): void
    {
        // Remove columns if they exist
        if (Schema::hasTable('atk_products') && Schema::hasColumn('atk_products', 'image')) {
            Schema::table('atk_products', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }

        if (Schema::hasTable('wash_services') && Schema::hasColumn('wash_services', 'image')) {
            Schema::table('wash_services', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }

        if (Schema::hasTable('atk_transactions') && Schema::hasColumn('atk_transactions', 'employee_id')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            });
        }

        if (Schema::hasTable('wash_transactions') && Schema::hasColumn('wash_transactions', 'employee_id')) {
            Schema::table('wash_transactions', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            });
        }
    }
};
