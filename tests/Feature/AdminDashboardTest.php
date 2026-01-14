<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $adminRole;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles
        $this->adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        
        // Create Delete Permission and attach to admin (admin usually has all, but explicit for test)
        $deletePermission = Permission::firstOrCreate(
            ['name' => 'ticket.delete'],
            ['label' => 'Delete Ticket', 'group' => 'Ticket Management']
        );
        $this->adminRole->permissions()->syncWithoutDetaching([$deletePermission->id]);

        // Create Admin User
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        // Create Customer
        $this->customer = \App\Models\Customer::create([
            'name' => 'Test Customer',
            'address' => 'Test Address',
            'phone' => '081234567890',
            'status' => 'active',
            'package' => 'Home 50Mbps',
        ]);
    }

    public function test_admin_can_delete_ticket()
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TKT-TEST-001',
            'customer_id' => $this->customer->id,
            'subject' => 'Ticket to Delete',
            'priority' => 'low',
            'status' => 'open',
            'type' => 'gangguan',
        ]);

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);

        $response = $this->actingAs($this->admin)->delete(route('tickets.destroy', $ticket));

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_dashboard_shows_monthly_recap()
    {
        // Create tickets in different months
        // Current month
        Ticket::create([
            'ticket_number' => 'TKT-NOW-001',
            'customer_id' => $this->customer->id,
            'subject' => 'Current Month Ticket',
            'priority' => 'low',
            'status' => 'open',
            'type' => 'gangguan',
            'created_at' => now(),
        ]);

        // Last month
        $lastMonthTicket = Ticket::create([
            'ticket_number' => 'TKT-LAST-001',
            'customer_id' => $this->customer->id,
            'subject' => 'Last Month Ticket',
            'priority' => 'low',
            'status' => 'solved',
            'type' => 'gangguan',
        ]);
        $lastMonthTicket->created_at = now()->subMonth();
        $lastMonthTicket->save();

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('ticketRecap');
        
        // Check if data is correct
        $recap = $response->viewData('ticketRecap');
        
        // Find current month
        $currentMonthName = now()->format('F');
        $currentMonthData = collect($recap)->firstWhere('month', $currentMonthName);
        
        $this->assertNotNull($currentMonthData);
        $this->assertEquals(1, $currentMonthData['total']);
        
        // Find last month
        $lastMonthName = now()->subMonth()->format('F');
        $lastMonthData = collect($recap)->firstWhere('month', $lastMonthName);
        
        // Only check if it's the same year
        if (now()->year == now()->subMonth()->year) {
             $this->assertNotNull($lastMonthData);
             $this->assertEquals(1, $lastMonthData['total']);
             $this->assertEquals(1, $lastMonthData['resolved']);
        }
    }
}
