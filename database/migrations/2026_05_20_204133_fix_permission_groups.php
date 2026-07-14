<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fixes = [
            'closure' => 'Closure Management',
            'OLT' => 'OLT Management',
            'Ticket' => 'Ticket Management',
            'Role' => 'Role Management',
            'Setting' => 'Settings',
            'User' => 'User Management',
        ];

        foreach ($fixes as $oldGroup => $newGroup) {
            DB::table('permissions')
                ->where('group', $oldGroup)
                ->update(['group' => $newGroup]);
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
