<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('full_name');
                $table->date('date_of_birth')->nullable();
                $table->string('gender', 10)->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email')->nullable();
                $table->string('nik', 32)->nullable();
                $table->string('position')->nullable();
                $table->string('department')->nullable();
                $table->date('join_date')->nullable();
                $table->string('employment_status', 20)->nullable();
                $table->string('document_path')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('employees', function (Blueprint $table) {
                if (! Schema::hasColumn('employees', 'employment_status')) {
                    $table->string('employment_status', 20)->nullable()->after('join_date');
                }
                if (! Schema::hasColumn('employees', 'document_path')) {
                    $table->string('document_path')->nullable()->after('employment_status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
