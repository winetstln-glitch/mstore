<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        
        // Create permissions required for settings
        $viewPerm = Permission::firstOrCreate(
            ['name' => 'setting.view'],
            ['label' => 'View Settings', 'group' => 'Settings']
        );
        $updatePerm = Permission::firstOrCreate(
            ['name' => 'setting.update'],
            ['label' => 'Update Settings', 'group' => 'Settings']
        );
        
        $adminRole->permissions()->attach([$viewPerm->id, $updatePerm->id]);
        
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        // Run the migration to seed settings (since RefreshDatabase wipes it)
        $this->artisan('migrate');
    }

    public function test_admin_can_view_attendance_settings()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Weekly Work Schedule');
        $response->assertSee('Monday');
        $response->assertSee('Sunday');
    }

    public function test_admin_can_update_work_schedule()
    {
        // Ensure the setting exists
        $this->assertDatabaseHas('settings', ['key' => 'work_schedule']);

        $newSchedule = [
            'Monday' => ['enabled' => '1', 'start' => '09:00', 'end' => '18:00'],
            'Tuesday' => ['enabled' => '1', 'start' => '09:00', 'end' => '18:00'],
            // ... other days can be omitted if the controller handles partial updates, 
            // but our controller replaces the value. 
            // The form submission sends ALL days.
        ];
        
        // We need to simulate the full form submission structure
        // The view sends: work_schedule[Monday][enabled], work_schedule[Monday][start], etc.
        
        $payload = [
            'work_schedule' => [
                'Monday' => ['enabled' => '1', 'start' => '09:00', 'end' => '18:00'],
                'Tuesday' => ['enabled' => '1', 'start' => '09:00', 'end' => '18:00'],
                'Wednesday' => ['enabled' => '0', 'start' => '00:00', 'end' => '00:00'],
                'Thursday' => ['enabled' => '0', 'start' => '00:00', 'end' => '00:00'],
                'Friday' => ['enabled' => '0', 'start' => '00:00', 'end' => '00:00'],
                'Saturday' => ['enabled' => '0', 'start' => '00:00', 'end' => '00:00'],
                'Sunday' => ['enabled' => '0', 'start' => '00:00', 'end' => '00:00'],
            ],
            'attendance_radius' => '200',
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify database
        $setting = Setting::where('key', 'work_schedule')->first();
        $schedule = json_decode($setting->value, true);

        $this->assertEquals('09:00', $schedule['Monday']['start']);
        $this->assertEquals('18:00', $schedule['Monday']['end']);
        $this->assertEquals('1', $schedule['Monday']['enabled']);
        
        // Verify other setting
        $this->assertDatabaseHas('settings', [
            'key' => 'attendance_radius',
            'value' => '200'
        ]);
    }
}
