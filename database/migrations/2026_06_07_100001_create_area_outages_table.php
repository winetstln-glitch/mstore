<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('area_outages')) {
            return;
        }

        Schema::create('area_outages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['outage', 'maintenance', 'fiber_cut', 'olt_down'])->default('outage');
            $table->string('status')->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('estimated_finish_at')->nullable();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('odp_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('olt_id')->nullable()->constrained()->nullOnDelete();
            $table->json('affected_areas')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('area_outages');
    }
};
