<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('atk_cash_movements')
            ->whereIn('movement_type', ['opening', 'closing'])
            ->delete();
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan
    }
};