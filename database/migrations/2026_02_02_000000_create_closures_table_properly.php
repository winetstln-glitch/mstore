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
        Schema::dropIfExists('closures_table_and_update_topology');

        if (! Schema::hasTable('closures')) {
            Schema::create('closures', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('odc_id')->nullable()->constrained('odcs')->onDelete('set null');
                $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->integer('capacity')->default(0);
                $table->integer('filled')->default(0);
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('closures');
    }
};
