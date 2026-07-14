<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('tax_id')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->string('country')->default('ID');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 20);
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_branches');
        Schema::dropIfExists('companies');
    }
};