<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cashes')) {
            Schema::create('cashes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('balance', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // Seed default cash if not exists
        if (! DB::table('cashes')->where('name', 'Kas Utama')->exists()) {
            DB::table('cashes')->insert([
                'name' => 'Kas Utama',
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cashes');
    }
};
