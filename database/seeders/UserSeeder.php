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
        $adminRole = Role::where('name', 'admin')->first();
        $nocRole = Role::where('name', 'noc')->first();
        $techRole = Role::where('name', 'technician')->first();

        User::updateOrCreate(
            ['email' => 'admin@mstore.local'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => 'password',
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'noc@mstore.local'],
            [
                'name' => 'NOC Officer',
                'username' => 'noc',
                'password' => 'password',
                'role_id' => $nocRole->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'tech1@mstore.local'],
            [
                'name' => 'Technician One',
                'username' => 'tech1',
                'password' => 'password',
                'role_id' => $techRole->id,
                'is_active' => true,
            ]
        );
    }
}
