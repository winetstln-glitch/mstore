<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginUsernameOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_semua_role_bisa_login_pakai_username(): void
    {
        $cases = [
            ['role' => 'admin', 'redirect' => route('dashboard')],
            ['role' => 'technician', 'redirect' => route('dashboard')],
            ['role' => 'noc', 'redirect' => route('dashboard')],
            ['role' => 'kasir-atk', 'redirect' => route('dashboard')],
            ['role' => 'kasir-wash', 'redirect' => route('attendance.create')],
            ['role' => 'karyawan-wash', 'redirect' => route('attendance.create')],
            ['role' => 'staff', 'redirect' => route('dashboard')],
            ['role' => 'customer', 'redirect' => route('client.onu-wifi.show')],
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

        $response->assertRedirect(route('client.onu-wifi.show'));
        $this->assertAuthenticated();
        $customer->refresh();
        $this->assertNotNull($customer->user_id);
        $this->assertSame('customer', auth()->user()->role?->name);
    }

    public function test_public_auth_and_password_reset_routes_are_throttled(): void
    {
        $routes = Route::getRoutes();
        $loginMiddleware = $routes->match(Request::create('/login', 'POST'))->gatherMiddleware();
        $forgotMiddleware = $routes->match(Request::create('/forgot-password', 'POST'))->gatherMiddleware();
        $resetMiddleware = $routes->match(Request::create('/reset-password', 'POST'))->gatherMiddleware();

        $this->assertContains('throttle:10,1', $loginMiddleware);
        $this->assertContains('throttle:5,1', $forgotMiddleware);
        $this->assertContains('throttle:5,1', $resetMiddleware);
    }
}
