<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Installation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $technician;
    protected $adminRole;
    protected $techRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles
        $this->adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $this->techRole = Role::create(['name' => 'technician', 'label' => 'Technician']);

        $ticketCompletePermission = Permission::firstOrCreate(
            ['name' => 'ticket.complete'],
            ['label' => 'Complete Ticket', 'group' => 'Ticket Management']
        );
        $this->techRole->permissions()->syncWithoutDetaching([$ticketCompletePermission->id]);

        // Create Users
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        $this->technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech@test.com',
            'password' => Hash::make('password'),
            'role_id' => $this->techRole->id,
            'is_active' => true,
        ]);
    }

    public function test_full_workflow_scenario()
    {
        // 1. Admin logs in and creates a customer
        $response = $this->actingAs($this->admin)->post(route('customers.store'), [
            'name' => 'Budi Santoso',
            'address' => 'Jl. Merdeka No. 10',
            'phone' => '08123456789',
            'package' => 'Home 20Mbps',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['name' => 'Budi Santoso']);
        $customer = Customer::first();

        // 2. Admin creates a ticket for the customer
        $response = $this->actingAs($this->admin)->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'subject' => 'Internet Slow',
            'type' => 'gangguan',
            'priority' => 'high',
            'description' => 'Internet very slow since morning',
        ]);

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseHas('tickets', ['subject' => 'Internet Slow', 'status' => 'open']);
        $ticket = Ticket::first();

        // 2b. Admin views dashboard and sees the new ticket
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Overview');
        $response->assertSee('Internet Slow'); // Recent tickets
        $response->assertSee('Customers');

        // 3. Admin assigns ticket to technician
        $response = $this->actingAs($this->admin)->put(route('tickets.update', $ticket), [
            'technicians' => [$this->technician->id],
            'status' => 'assigned',
            'subject' => 'Internet Slow', // required field
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'assigned'
        ]);
        $this->assertDatabaseHas('ticket_user', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->technician->id,
        ]);

        // 4. Technician logs in and views dashboard
        $response = $this->actingAs($this->technician)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('technician.dashboard');
        $response->assertSee('Internet Slow'); // Should see the assigned ticket

        // 5. Technician updates ticket to 'in_progress'
        $response = $this->actingAs($this->technician)->put(route('tickets.update', $ticket), [
            'status' => 'in_progress',
            'subject' => 'Internet Slow',
        ]);

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'in_progress']);
    }
}
