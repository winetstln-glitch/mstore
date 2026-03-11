<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\MixRadiusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class LoginUsernameOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $mock = Mockery::mock(MixRadiusService::class);
        $mock->shouldReceive('isAvailable')->andReturn(false);
        $mock->shouldReceive('verifyCredentials')->andReturn(['ok' => false]);
        $mock->shouldReceive('resolveUsernameById')->andReturnNull();
        $this->app->instance(MixRadiusService::class, $mock);
    }

    public function test_semua_role_bisa_login_pakai_username(): void
    {
        $cases = [
            ['role' => 'admin', 'redirect' => route('dashboard')],
            ['role' => 'technician', 'redirect' => route('dashboard')],
            ['role' => 'noc', 'redirect' => route('dashboard')],
            ['role' => 'kasir-atk', 'redirect' => route('dashboard')],
            ['role' => 'kasir-wash', 'redirect' => route('dashboard')],
            ['role' => 'staff', 'redirect' => route('dashboard')],
            ['role' => 'customer', 'redirect' => route('client.dashboard')],
        ];

        foreach ($cases as $index => $case) {
            $role = Role::create([
                'name' => $case['role'],
                'label' => strtoupper($case['role']),
            ]);

            $user = User::create([
                'name' => 'User '.$case['role'],
                'email' => $case['role'].$index.'@test.local',
                'username' => $case['role'].'_user_'.$index,
                'password' => Hash::make('password123'),
                'role_id' => $role->id,
                'is_active' => true,
            ]);

            $response = $this->post(route('login'), [
                'login' => $user->username,
                'password' => 'password123',
            ]);

            $response->assertRedirect($case['redirect']);
            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_login_email_ditolak_untuk_user_yang_sudah_punya_username(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'label' => 'Administrator',
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'username' => 'adminuser',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'login' => 'admin@test.local',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_user_lama_tanpa_username_bisa_login_menggunakan_email_penuh(): void
    {
        $role = Role::create([
            'name' => 'noc',
            'label' => 'NOC',
        ]);

        $user = User::create([
            'name' => 'Legacy Email',
            'email' => 'legacy.email@test.local',
            'username' => null,
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $user->forceFill(['username' => null])->saveQuietly();

        $response = $this->post(route('login'), [
            'login' => 'legacy.email@test.local',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_lama_tanpa_username_tetap_bisa_login_dengan_localpart_email(): void
    {
        $role = Role::create([
            'name' => 'noc',
            'label' => 'NOC',
        ]);

        $user = User::create([
            'name' => 'Legacy Noc',
            'email' => 'legacy.noc@test.local',
            'username' => null,
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $user->forceFill(['username' => null])->saveQuietly();

        $response = $this->post(route('login'), [
            'login' => 'legacy.noc',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_bisa_login_portal_dari_data_customer_tanpa_user_management(): void
    {
        Role::create([
            'name' => 'customer',
            'label' => 'Customer',
        ]);

        $customer = Customer::create([
            'name' => 'Pelanggan A',
            'status' => 'active',
            'pppoe_user' => 'cust_1001',
            'pppoe_password' => 'rahasia123',
        ]);

        $response = $this->post(route('login'), [
            'login' => 'cust_1001',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticated();
        $customer->refresh();
        $this->assertNotNull($customer->user_id);
        $this->assertSame('customer', auth()->user()->role?->name);
    }
}
