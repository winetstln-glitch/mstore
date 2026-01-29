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
            // Create new table with updated enum
            Schema::create('assets_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->string('asset_code')->nullable()->unique();
                $table->string('serial_number')->nullable()->unique();
                $table->string('mac_address')->nullable();
                
                // Updated Enum with 'pending_return'
                $table->enum('status', ['in_stock', 'deployed', 'maintenance', 'broken', 'lost', 'pending_return'])->default('in_stock');
                $table->enum('condition', ['good', 'damaged'])->default('good');
                
                // Fields from other migrations
                $table->string('latitude')->nullable();
                $table->string('longitude')->nullable();
                $table->boolean('is_returnable')->default(true); // Added from subsequent migration
                
                $table->nullableMorphs('holder'); 
                
                $table->json('meta_data')->nullable();
                
                $table->date('purchase_date')->nullable();
                $table->date('warranty_expiry')->nullable();
                
                $table->timestamps();
            });

            // Copy data
            // Note: We need to list columns explicitly to avoid issues if order changed, but usually insert into select * works if schema matches.
            // Safer to list common columns.
            $columns = [
                'id', 'inventory_item_id', 'asset_code', 'serial_number', 'mac_address', 
                'status', 'condition', 'latitude', 'longitude', 'is_returnable',
                'holder_type', 'holder_id', 'meta_data', 'purchase_date', 'warranty_expiry', 
                'created_at', 'updated_at'
            ];
            
            // We need to make sure we don't copy 'pending_return' status if it somehow existed (unlikely as it failed), 
            // but just copy raw data.
            // Since the old table didn't allow pending_return, all existing data is valid in new table.
            
            // Build column string
            $colString = implode(', ', $columns);
            
            DB::statement("INSERT INTO assets_new ($colString) SELECT $colString FROM assets");

            // Drop old table
            Schema::drop('assets');

            // Rename new table
            Schema::rename('assets_new', 'assets');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy down for this complex operation, and typically not needed for fix migrations.
        // We could revert to old schema but data with 'pending_return' would violate it.
    }
};
