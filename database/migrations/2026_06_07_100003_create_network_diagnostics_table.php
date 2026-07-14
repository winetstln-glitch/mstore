<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('network_diagnostics')) {
            return;
        }

        Schema::create('network_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('diagnosis_key')->index();
            $table->string('status')->default('pending');
            $table->text('summary')->nullable();
            $table->json('checks')->nullable();
            $table->json('genieacs_data')->nullable();
            $table->json('mikrotik_data')->nullable();
            $table->json('billing_data')->nullable();
            $table->json('area_outage_data')->nullable();
            $table->json('recommendations')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->boolean('ticket_needed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('network_diagnostics');
    }
};
