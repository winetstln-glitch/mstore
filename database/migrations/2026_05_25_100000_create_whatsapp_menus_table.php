<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_menus', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->unique();
            $table->string('type')->default('text'); // text, image, document, button, list
            $table->longText('response_text')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable(); // image/jpeg, application/pdf, etc.
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('hits_count')->default(0);
            $table->integer('priority')->default(0);
            $table->boolean('enable_fuzzy_match')->default(true);
            $table->json('variables')->nullable(); // supported variables
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_menus');
    }
};
