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
        Schema::create('schedule_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('week_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->unique(['year', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_periods');
    }
};
