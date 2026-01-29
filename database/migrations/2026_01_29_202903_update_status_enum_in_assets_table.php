<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we modify the column. For SQLite, it's more complex but typically Laravel abstraction handles basics or we skip check constraint.
        // Since we are likely on MySQL in production (Winets), or SQLite locally.
        
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support modifying enum columns easily without dropping/recreating.
            // But usually for SQLite in Laravel, enums are just varchars with no native constraint unless explicitly added.
            // We'll just ignore for SQLite or rely on application level validation.
            // However, if we want to be safe, we can try to change it.
            // But Doctrine DBAL is needed for change().
            // Let's assume application validation is primary for SQLite.
        } else {
            // MySQL
            DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('in_stock', 'deployed', 'maintenance', 'broken', 'lost', 'pending_return') DEFAULT 'in_stock'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            // Revert pending_return to in_stock before changing column
            DB::table('assets')->where('status', 'pending_return')->update(['status' => 'in_stock']);
            DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('in_stock', 'deployed', 'maintenance', 'broken', 'lost') DEFAULT 'in_stock'");
        }
    }
};
