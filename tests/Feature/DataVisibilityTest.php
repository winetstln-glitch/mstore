<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TechnicianAttendance;
use App\Models\TechnicianSchedule;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DataVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $techA;
    protected $techB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $techRole = Role::create(['name' => 'technician', 'label' => 'Technician']);

        // Create Permissions
        $perms = ['ticket.view', 'attendance.view', 'schedule.view']; // schedule.view might not exist, checking logic
        // Actually the code doesn't seem to enforce middleware permission checks on index explicitly in the snippet I saw,
        // but maybe global middleware or the test user needs them.
        // Let's create generic permissions.
        
        foreach(['ticket.view', 'attendance.view', 'schedule.view'] as $pName) {
            $p = Permission::create(['name' => $pName, 'label' => $pName, 'group' => 'test']);
            $techRole->permissions()->attach($p);
            $adminRole->permissions()->attach($p);
        }

        // Create Users
        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'name' => 'Admin User']);
        $this->techA = User::factory()->create(['role_id' => $techRole->id, 'name' => 'Tech A']);
        $this->techB = User::factory()->create(['role_id' => $techRole->id, 'name' => 'Tech B']);
    }

    public function test_ticket_visibility()
    {
        // Create Customer
        $customer = \App\Models\Customer::create([
            'name' => 'Test Customer',
            'address' => 'Test Address',
            'phone' => '08123456789',
            'package' => 'Home 20Mbps',
            'status' => 'active',
        ]);

        // Create Tickets
        $ticketA = Ticket::create([
            'customer_id' => $customer->id,
            'subject' => 'Ticket A',
            'type' => 'gangguan',
            'priority' => 'high',
            'status' => 'assigned',
            'description' => 'Test',
            'ticket_number' => 'T-001'
        ]);
        $ticketA->technicians()->attach($this->techA->id);

        $ticketB = Ticket::create([
            'customer_id' => $customer->id,
            'subject' => 'Ticket B',
            'type' => 'gangguan',
            'priority' => 'high',
            'status' => 'assigned',
            'description' => 'Test',
            'ticket_number' => 'T-002'
        ]);
        $ticketB->technicians()->attach($this->techB->id);

        // Tech A should see Ticket A but not Ticket B
        $response = $this->actingAs($this->techA)->get(route('tickets.index'));
        $response->assertStatus(200);
        $response->assertSee($ticketA->ticket_number);
        $response->assertDontSee($ticketB->ticket_number);

        // Admin should see both
        $response = $this->actingAs($this->admin)->get(route('tickets.index'));
        $response->assertStatus(200);
        $response->assertSee($ticketA->ticket_number);
        $response->assertSee($ticketB->ticket_number);
    }

    public function test_attendance_visibility()
    {
        // Create Attendance
        $attendanceA = TechnicianAttendance::create([
            'user_id' => $this->techA->id,
            'clock_in' => now(),
            'status' => 'present'
        ]);

        $attendanceB = TechnicianAttendance::create([
            'user_id' => $this->techB->id,
            'clock_in' => now(),
            'status' => 'present'
        ]);

        // Tech A should see their attendance but not Tech B's
        // Note: The view might show user names, so we check for that or specific IDs if possible.
        // Or check the data passed to the view.
        
        $response = $this->actingAs($this->techA)->get(route('attendance.index'));
        $response->assertStatus(200);
        // The view likely lists attendance records.
        // We can check if Tech B's name appears in the table rows? 
        // But Tech B might appear in the filter dropdown if not filtered correctly.
        // Let's check if the attendance record ID or unique data is visible.
        
        // Actually, easiest is to check if Tech B's name is in the response body IF the table shows names.
        // Assuming table shows names.
        $response->assertSee($this->techA->name);
        $response->assertDontSee($this->techB->name);

        // Admin should see both
        $response = $this->actingAs($this->admin)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee($this->techA->name);
        $response->assertSee($this->techB->name);
    }

    public function test_schedule_visibility()
    {
        // Create Schedules
        TechnicianSchedule::create([
            'user_id' => $this->techA->id,
            'week_number' => 10,
            'year' => 2026,
            'status' => 'piket'
        ]);

        TechnicianSchedule::create([
            'user_id' => $this->techB->id,
            'week_number' => 10,
            'year' => 2026,
            'status' => 'piket'
        ]);

        // Tech A should see their row (Tech A name) but not Tech B's row
        $response = $this->actingAs($this->techA)->get(route('schedules.index'));
        $response->assertStatus(200);
        $response->assertSee($this->techA->name);
        $response->assertDontSee($this->techB->name);

        // Admin should see both
        $response = $this->actingAs($this->admin)->get(route('schedules.index'));
        $response->assertStatus(200);
        $response->assertSee($this->techA->name);
        $response->assertSee($this->techB->name);
    }
}
