<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TechnicianAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_attendance_widget()
    {
        // Create Admin with attendance permission
        $role = Role::create(['name' => 'admin', 'label' => 'Admin']);
        $permission = Permission::firstOrCreate(
            ['name' => 'attendance.view'],
            ['label' => 'View Attendance', 'group' => 'Attendance']
        );
        $role->permissions()->attach($permission);
        
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Absensi Saya Hari Ini');
        $response->assertSee('Belum Hadir');
        $response->assertSee(__('Clock In'));
    }

    public function test_technician_dashboard_shows_attendance_widget()
    {
        // Create Technician with attendance permission
        $role = Role::create(['name' => 'technician', 'label' => 'Technician']);
        $permission = Permission::firstOrCreate(
            ['name' => 'attendance.view'],
            ['label' => 'View Attendance', 'group' => 'Attendance']
        );
        $role->permissions()->attach($permission);
        
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Absensi Saya Hari Ini');
        $response->assertSee('Belum Hadir');
        $response->assertSee(__('Clock In'));
    }

    public function test_dashboard_shows_attendance_status_when_clocked_in()
    {
        // Create Technician with attendance permission
        $role = Role::create(['name' => 'technician', 'label' => 'Technician']);
        $permission = Permission::firstOrCreate(
            ['name' => 'attendance.view'],
            ['label' => 'View Attendance', 'group' => 'Attendance']
        );
        $role->permissions()->attach($permission);
        
        $user = User::factory()->create(['role_id' => $role->id]);

        // Create attendance record
        $clockIn = now()->subHour();
        TechnicianAttendance::create([
            'user_id' => $user->id,
            'clock_in' => $clockIn,
            'status' => 'present',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'photo_in' => 'test.jpg'
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Absensi Saya Hari Ini');
        $response->assertSee('Hadir');
    }
}
