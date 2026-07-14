<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kasbon_loans', function (Blueprint $table) {
            // Rename principal to principal_amount
            $table->renameColumn('principal', 'principal_amount');
            
            // Rename tenor to tenor_months
            $table->renameColumn('tenor', 'tenor_months');
            
            // Add missing columns
            $table->decimal('monthly_installment', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kasbon_loans', function (Blueprint $table) {
            // Revert changes
            $table->renameColumn('principal_amount', 'principal');
            $table->renameColumn('tenor_months', 'tenor');
            $table->dropColumn(['monthly_installment', 'created_by']);
        });
    }
};