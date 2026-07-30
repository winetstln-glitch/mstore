<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('subtitle', 255)->nullable();
            $table->string('image_path', 500);
            $table->string('mobile_image_path', 500)->nullable();
            $table->string('cta_text', 80)->nullable();
            $table->string('url_cta', 500)->nullable();
            $table->boolean('open_new_tab')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('page_target', 50)->default('all')
                ->comment('all, login, landing, voucher, member, residential');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'page_target', 'sort_order']);
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_banners');
    }
};
