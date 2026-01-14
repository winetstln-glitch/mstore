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
        // 1. Create odcs table
        Schema::create('odcs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('olt_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('capacity')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Add coordinates to olts table
        Schema::table('olts', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('description');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        // 3. Add odc_id to odps table
        Schema::table('odps', function (Blueprint $table) {
            $table->foreignId('odc_id')->nullable()->after('id')->constrained('odcs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropForeign(['odc_id']);
            $table->dropColumn('odc_id');
        });

        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::dropIfExists('odcs');
    }
};
