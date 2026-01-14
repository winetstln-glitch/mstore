<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Permission;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $permission = Permission::firstOrCreate(
            ['name' => 'user.delete'],
            ['label' => 'Delete User', 'group' => 'User Management']
        );
        $adminRole->permissions()->attach($permission);
        
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_user_with_attendance()
    {
        $user = User::create([
            'name' => 'Technician',
            'email' => 'tech@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Create attendance record directly in DB to avoid model dependencies if any
        DB::table('technician_attendances')->insert([
            'user_id' => $user->id,
            'clock_in' => now(),
            'status' => 'present',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('technician_attendances', ['user_id' => $user->id]);

        $response = $this->actingAs($this->admin)->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        // Attendance should be cascaded (deleted)
        $this->assertDatabaseMissing('technician_attendances', ['user_id' => $user->id]);
    }

    public function test_admin_can_delete_user_with_transactions()
    {
        $user = User::create([
            'name' => 'Cashier',
            'email' => 'cashier@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Create transaction record
        DB::table('transactions')->insert([
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 100000,
            'transaction_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('transactions', ['user_id' => $user->id]);

        $response = $this->actingAs($this->admin)->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));
        
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        // Transaction should be kept but user_id set to null
        $this->assertDatabaseHas('transactions', ['user_id' => null, 'amount' => 100000]);
    }
}
