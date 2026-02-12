<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agent_deposits')) {
            Schema::create('agent_deposits', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('balance', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // Seed default agent deposit if not exists
        if (!DB::table('agent_deposits')->where('name', 'Deposit Agen Bank')->exists()) {
            DB::table('agent_deposits')->insert([
                'name' => 'Deposit Agen Bank',
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_deposits');
    }
};
