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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('category')->default('general')->after('name'); // e.g. device, fiber, tool, vehicle
            $table->string('type')->nullable()->after('category'); // e.g. router, cable, splicer
            $table->string('brand')->nullable()->after('type');
            $table->string('model')->nullable()->after('brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['category', 'type', 'brand', 'model']);
        });
    }
};
