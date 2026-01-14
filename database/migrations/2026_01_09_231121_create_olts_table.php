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
        if (!Schema::hasTable('olts')) {
            Schema::create('olts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('host');
                $table->integer('port')->default(23); // Telnet default, 22 for SSH
                $table->string('username');
                $table->string('password');
                $table->enum('type', ['epon', 'gpon', 'xpon'])->default('epon');
                $table->string('brand')->default('zte'); // zte, huawei, hsgq, vsol
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olts');
    }
};
