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
        // Fix technician_attendances constraints
        Schema::table('technician_attendances', function (Blueprint $table) {
            // Drop existing foreign key (assuming default naming convention)
            // If using SQLite, dropping foreign keys requires table recreation or special handling, 
            // but Laravel handles it reasonably well in recent versions or we might need to be careful.
            // However, dropForeign is supported if we just re-add it.
            
            // Note: SQLite doesn't support dropping foreign keys directly in all versions/drivers easily without table copy.
            // But Laravel's Schema builder tries to handle this.
            
            $table->dropForeign(['user_id']);
            
            // Re-add with cascade
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // Fix transactions constraints
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users'); // Defaults to restrict
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users'); // Defaults to restrict
        });
    }
};
