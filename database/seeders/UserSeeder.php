<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@mstore.local',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'NOC Officer',
            'email' => 'noc@mstore.local',
            'password' => Hash::make('password'),
            'role_id' => $nocRole->id,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Technician One',
            'email' => 'tech1@mstore.local',
            'password' => Hash::make('password'),
            'role_id' => $techRole->id,
            'is_active' => true,
        ]);
    }
}
