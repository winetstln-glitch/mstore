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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('asset_code')->nullable()->unique(); // ISP-CODE-001
            $table->string('serial_number')->nullable()->unique();
            $table->string('mac_address')->nullable();
            
            // Status and Condition
            $table->enum('status', ['in_stock', 'deployed', 'maintenance', 'broken', 'lost'])->default('in_stock');
            $table->enum('condition', ['good', 'damaged'])->default('good');
            
            // Polymorphic relation for Holder (Customer, User/Technician, or Null for Warehouse)
            // holder_type: App\Models\Customer, App\Models\User
            // holder_id: ID of the holder
            $table->nullableMorphs('holder'); 
            
            // Flexible metadata for device specifics (firmware, IP, etc)
            $table->json('meta_data')->nullable();
            
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
