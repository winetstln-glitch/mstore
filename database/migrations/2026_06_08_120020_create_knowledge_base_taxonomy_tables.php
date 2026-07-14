<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('knowledge_base_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        Schema::create('knowledge_base_document_tag', function (Blueprint $table) {
            $table->foreignId('knowledge_base_id')->constrained('knowledge_bases')->cascadeOnDelete();
            $table->foreignId('knowledge_base_tag_id')->constrained('knowledge_base_tags')->cascadeOnDelete();
            $table->primary(['knowledge_base_id', 'knowledge_base_tag_id']);
        });

        Schema::table('knowledge_bases', function (Blueprint $table) {
            if (! Schema::hasColumn('knowledge_bases', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('category')->constrained('knowledge_base_categories')->nullOnDelete();
                $table->index(['category_id', 'status']);
            }
            if (! Schema::hasColumn('knowledge_bases', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('status')->index();
            }
            if (! Schema::hasColumn('knowledge_bases', 'content_hash')) {
                $table->string('content_hash', 64)->nullable()->after('content')->index();
            }
            if (! Schema::hasColumn('knowledge_bases', 'embedding')) {
                $table->json('embedding')->nullable()->after('content_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_bases', 'embedding')) {
                $table->dropColumn('embedding');
            }
            if (Schema::hasColumn('knowledge_bases', 'content_hash')) {
                $table->dropColumn('content_hash');
            }
            if (Schema::hasColumn('knowledge_bases', 'published_at')) {
                $table->dropColumn('published_at');
            }
            if (Schema::hasColumn('knowledge_bases', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
        });

        Schema::dropIfExists('knowledge_base_document_tag');
        Schema::dropIfExists('knowledge_base_tags');
        Schema::dropIfExists('knowledge_base_categories');
    }
};

