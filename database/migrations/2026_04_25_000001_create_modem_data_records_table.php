<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modem_data_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('modem_type')->nullable();
            $table->string('mac_address', 20);
            $table->string('serial_number', 100);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 7)->nullable();
            $table->string('coordinates')->nullable();
            $table->timestamps();

            $table->index('customer_name');
            $table->index('mac_address');
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modem_data_records');
    }
};
