<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CashierAndEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        $roles = [
            ['name' => 'atk.cashier', 'label' => 'Kasir Toko (ATK)'],
            ['name' => 'wash.cashier', 'label' => 'Kasir Layanan (Wash)'],
            ['name' => 'wash.employee', 'label' => 'Karyawan Cuci (Steam)'],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Assign Permissions
            if ($role->name === 'atk.cashier') {
                $permissions = Permission::whereIn('name', [
                    'atk.view',
                    'atk.pos',
                    'atk.report',
                    'profile.view',
                    'profile.update',
                    'attendance.create', // If they need to clock in
                    'attendance.view',
                ])->get();
                $role->permissions()->sync($permissions);
            } elseif ($role->name === 'wash.cashier') {
                $permissions = Permission::whereIn('name', [
                    'wash.view',
                    'wash.pos',
                    'wash.report',
                    'profile.view',
                    'profile.update',
                    'attendance.create',
                    'attendance.view',
                ])->get();
                $role->permissions()->sync($permissions);
            } elseif ($role->name === 'wash.employee') {
                // Employees who wash cars might not need POS access, just attendance
                $permissions = Permission::whereIn('name', [
                    'attendance.create',
                    'attendance.view',
                    'profile.view',
                    'profile.update',
                    'leave.create',
                    'leave.view',
                ])->get();
                $role->permissions()->sync($permissions);
            }
        }

        // 2. Create Dummy Users
        $users = [
            [
                'name' => 'Kasir Toko',
                'email' => 'kasirtoko@mstore.id',
                'password' => 'password',
                'role' => 'atk.cashier',
            ],
            [
                'name' => 'Kasir Layanan',
                'email' => 'kasirlayanan@mstore.id',
                'password' => 'password',
                'role' => 'wash.cashier',
            ],
            [
                'name' => 'Karyawan Steam 1',
                'email' => 'steam1@mstore.id',
                'password' => 'password',
                'role' => 'wash.employee',
            ],
            [
                'name' => 'Karyawan Steam 2',
                'email' => 'steam2@mstore.id',
                'password' => 'password',
                'role' => 'wash.employee',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'is_active' => true,
                ]
            );

            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }
}
