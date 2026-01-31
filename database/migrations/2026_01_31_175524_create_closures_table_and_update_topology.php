<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('coordinates')->nullable(); // lat,lng
            $table->string('parent_type')->nullable(); // 'App\Models\Olt' or 'App\Models\Odc'
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['parent_type', 'parent_id']);
        });

        Schema::table('odcs', function (Blueprint $table) {
            $table->foreignId('closure_id')->nullable()->after('olt_id')->constrained('closures')->nullOnDelete();
        });

        Schema::table('odps', function (Blueprint $table) {
            $table->foreignId('closure_id')->nullable()->after('odc_id')->constrained('closures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropForeign(['closure_id']);
            $table->dropColumn('closure_id');
        });

        Schema::table('odcs', function (Blueprint $table) {
            $table->dropForeign(['closure_id']);
            $table->dropColumn('closure_id');
        });

        Schema::dropIfExists('closures');
    }
};
