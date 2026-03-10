<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUsers = [
            ['role' => 'admin', 'name' => 'Super Admin', 'email' => 'admin@mstore.local', 'username' => 'admin'],
            ['role' => 'noc', 'name' => 'NOC Officer', 'email' => 'noc@mstore.local', 'username' => 'noc'],
            ['role' => 'technician', 'name' => 'Technician One', 'email' => 'tech1@mstore.local', 'username' => 'technician1'],
            ['role' => 'kasir-atk', 'name' => 'Kasir ATK', 'email' => 'kasir.atk@mstore.local', 'username' => 'kasir_atk'],
            ['role' => 'kasir-wash', 'name' => 'Kasir Wash', 'email' => 'kasir.wash@mstore.local', 'username' => 'kasir_wash'],
            ['role' => 'finance', 'name' => 'Staff Finance', 'email' => 'staf@mstore.local', 'username' => 'staf'],
        ];

        foreach ($defaultUsers as $data) {
            $role = Role::where('name', $data['role'])->first();
            if (! $role) {
                continue;
            }

            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => 'password',
                    'role_id' => $role->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
