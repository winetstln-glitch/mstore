<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('phone', 32)->index();
            $table->string('email')->nullable()->index();
            $table->string('service_interest', 64)->nullable()->index();
            $table->string('coverage_area')->nullable();
            $table->text('message')->nullable();
            $table->string('source', 64)->default('landing')->index();
            $table->string('status', 32)->default('new')->index();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};

