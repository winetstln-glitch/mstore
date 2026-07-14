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
        Schema::table('regions', function (Blueprint $table) {
            $table->string('abbreviation', 10)->nullable()->after('name');
        });

        Schema::table('odps', function (Blueprint $table) {
            $table->string('kampung')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('abbreviation');
        });

        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn('kampung');
        });
    }
};
