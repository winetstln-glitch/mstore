<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function insertPermission(array $data): void
    {
        $exists = DB::table('permissions')->where('name', $data['name'])->exists();
        if (!$exists) {
            DB::table('permissions')->insert($data);
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->insertPermission(['name' => 'olt.view', 'label' => 'Lihat OLT', 'group' => 'OLT Management']);
        $this->insertPermission(['name' => 'olt.create', 'label' => 'Tambah OLT', 'group' => 'OLT Management']);
        $this->insertPermission(['name' => 'olt.edit', 'label' => 'Edit OLT', 'group' => 'OLT Management']);
        $this->insertPermission(['name' => 'olt.delete', 'label' => 'Hapus OLT', 'group' => 'OLT Management']);
        $this->insertPermission(['name' => 'olt.poll', 'label' => 'Polling OLT', 'group' => 'OLT Management']);
        $this->insertPermission(['name' => 'ont.view', 'label' => 'Lihat ONU', 'group' => 'OLT Management']);
        $this->insertPermission(['name' => 'ont.edit', 'label' => 'Edit ONU', 'group' => 'OLT Management']);
        $this->insertPermission(['name' => 'ont.delete', 'label' => 'Hapus ONU', 'group' => 'OLT Management']);
        $this->insertPermission(['name' => 'ont.reboot', 'label' => 'Reboot ONU', 'group' => 'OLT Management']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'olt.view', 'olt.create', 'olt.edit', 'olt.delete', 'olt.poll',
            'ont.view', 'ont.edit', 'ont.delete', 'ont.reboot'
        ])->delete();
    }
};
