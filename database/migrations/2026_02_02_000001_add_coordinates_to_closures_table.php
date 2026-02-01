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
        Schema::table('closures', function (Blueprint $table) {
            if (!Schema::hasColumn('closures', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('region_id');
            }
            if (!Schema::hasColumn('closures', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('closures', 'capacity')) {
                $table->integer('capacity')->default(0)->after('longitude');
            }
            if (!Schema::hasColumn('closures', 'filled')) {
                $table->integer('filled')->default(0)->after('capacity');
            }
            if (!Schema::hasColumn('closures', 'description')) {
                $table->text('description')->nullable()->after('filled');
            }
            if (!Schema::hasColumn('closures', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
            if (!Schema::hasColumn('closures', 'odc_id')) {
                $table->foreignId('odc_id')->nullable()->constrained('odcs')->onDelete('set null')->after('name');
            }
            if (!Schema::hasColumn('closures', 'region_id')) {
                $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null')->after('odc_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('closures', function (Blueprint $table) {
            $columns = ['latitude', 'longitude', 'capacity', 'filled', 'description', 'image', 'odc_id', 'region_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('closures', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
