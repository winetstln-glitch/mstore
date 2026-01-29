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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Create new table with updated enum and constraints
            Schema::create('assets_temp_fix', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->string('asset_code')->nullable()->unique();
                $table->string('serial_number')->nullable()->unique();
                $table->string('mac_address')->nullable();
                
                // Explicitly allow 'pending_return' in the check constraint
                $table->string('status')->default('in_stock'); 
                // In SQLite, enums are TEXT. We can either use string (no check) or enum (check).
                // Using string is safer for SQLite to avoid future constraint issues, 
                // or we can use enum if we want strictness.
                // Let's use enum but ensure 'pending_return' is in the list.
                // $table->enum('status', ['in_stock', 'deployed', 'maintenance', 'broken', 'lost', 'pending_return'])->default('in_stock');
                
                $table->enum('condition', ['good', 'damaged'])->default('good');
                
                $table->string('latitude')->nullable();
                $table->string('longitude')->nullable();
                $table->boolean('is_returnable')->default(true);
                
                $table->nullableMorphs('holder'); 
                
                $table->json('meta_data')->nullable();
                
                $table->date('purchase_date')->nullable();
                $table->date('warranty_expiry')->nullable();
                
                $table->timestamps();
            });

            // Copy data
            // We select columns that match.
            // Note: We need to handle potential missing columns if previous migrations failed? 
            // Assuming standard state.
            $columns = [
                'id', 'inventory_item_id', 'asset_code', 'serial_number', 'mac_address', 
                'status', 'condition', 'latitude', 'longitude', 'is_returnable',
                'holder_type', 'holder_id', 'meta_data', 'purchase_date', 'warranty_expiry', 
                'created_at', 'updated_at'
            ];
            
            $colString = implode(', ', $columns);
            
            DB::statement("INSERT INTO assets_temp_fix ($colString) SELECT $colString FROM assets");

            // Drop old table
            Schema::drop('assets');

            // Rename new table
            Schema::rename('assets_temp_fix', 'assets');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down
    }
};
