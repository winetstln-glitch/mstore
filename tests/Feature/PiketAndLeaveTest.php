<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class PiketAndLeaveTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $technician;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles and Permissions
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $techRole = Role::create(['name' => 'technician', 'label' => 'Technician']);

        // Create Permissions
        $perms = ['leave.manage', 'schedule.manage', 'setting.view'];
        foreach ($perms as $p) {
            $perm = Permission::create(['name' => $p, 'label' => $p, 'group' => 'attendance']);
            $adminRole->permissions()->attach($perm);
        }

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        $this->technician = User::factory()->create([
            'role_id' => $techRole->id,
        ]);

        // Ensure quota setting exists
        Setting::updateOrCreate(
            ['key' => 'technician_leave_quota'],
            ['value' => '3', 'group' => 'attendance', 'type' => 'number']
        );
    }

    public function test_admin_can_update_schedule_period()
    {
        $year = 2026;
        $week = 10;
        $start = '2026-03-02';
        $end = '2026-03-08';

        $response = $this->actingAs($this->admin)->post(route('schedules.updatePeriod'), [
            'year' => $year,
            'week_number' => $week,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schedule_periods', [
            'year' => $year,
            'week_number' => $week,
            'start_date' => $start . ' 00:00:00',
            'end_date' => $end . ' 00:00:00',
        ]);
    }

    public function test_admin_can_view_schedule_page()
    {
        $response = $this->actingAs($this->admin)->get(route('schedules.index'));
        $response->assertStatus(200);
        $response->assertViewIs('schedules.index');
    }

    public function test_admin_can_assign_schedule()
    {
        $response = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'user_id' => $this->technician->id,
            'week_number' => 10,
            'year' => 2026,
            'status' => 'piket',
            'notes' => 'Test Note'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('technician_schedules', [
            'user_id' => $this->technician->id,
            'week_number' => 10,
            'year' => 2026,
            'status' => 'piket',
        ]);
    }

    public function test_technician_can_request_leave_within_quota()
    {
        // Request 2 days (within 3 days quota)
        $start = Carbon::now()->addDays(1);
        $end = Carbon::now()->addDays(2); // 2 days

        $response = $this->actingAs($this->technician)->post(route('leave-requests.store'), [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'reason' => 'Sick leave',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->technician->id,
            'reason' => 'Sick leave',
            'status' => 'pending',
        ]);
    }

    public function test_technician_cannot_exceed_leave_quota()
    {
        // 1. Approve 2 days first
        LeaveRequest::create([
            'user_id' => $this->technician->id,
            'start_date' => Carbon::now()->startOfMonth()->addDays(1),
            'end_date' => Carbon::now()->startOfMonth()->addDays(2),
            'reason' => 'Approved Leave',
            'status' => 'approved',
        ]);

        // 2. Try to request 2 more days (Total 4 > 3)
        $start = Carbon::now()->endOfMonth()->subDays(2);
        $end = Carbon::now()->endOfMonth()->subDays(1);

        $response = $this->actingAs($this->technician)->post(route('leave-requests.store'), [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'reason' => 'Over quota',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error'); // Should have error message
        
        // Assert NOT created
        $this->assertDatabaseMissing('leave_requests', [
            'reason' => 'Over quota',
        ]);
    }

    public function test_admin_can_approve_leave()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->technician->id,
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(6),
            'reason' => 'Please approve',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->put(route('leave-requests.update', $leave), [
            'status' => 'approved',
        ]);

        $response->assertRedirect();
        
        $this->assertEquals('approved', $leave->fresh()->status);
        $this->assertEquals($this->admin->id, $leave->fresh()->approved_by);
    }
}
