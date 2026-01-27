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
        Schema::create('map_connections', function (Blueprint $table) {
            $table->id();
            $table->string('from_type'); // e.g., 'odc', 'odp', 'htb'
            $table->unsignedBigInteger('from_id');
            $table->string('to_type');   // e.g., 'odp', 'htb', 'customer'
            $table->unsignedBigInteger('to_id');
            $table->json('waypoints')->nullable();
            $table->timestamps();

            $table->unique(['from_type', 'from_id', 'to_type', 'to_id'], 'map_conn_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_connections');
    }
};
