<?php

namespace Tests\Unit;

use App\Support\DefaultRolePermissions;
use Tests\TestCase;

class DefaultRolePermissionsTest extends TestCase
{
    public function test_primary_role_templates_cover_all_default_labels(): void
    {
        $templates = collect(DefaultRolePermissions::primaryDefinitions())->pluck('label')->values()->all();

        $this->assertContains('Administrator', $templates);
        $this->assertContains('Direktur', $templates);
        $this->assertContains('Network Operations Center', $templates);
        $this->assertContains('Technician', $templates);
        $this->assertContains('Coordinator', $templates);
        $this->assertContains('Customer Service', $templates);
        $this->assertContains('HRD', $templates);
        $this->assertContains('Finance Staff', $templates);
        $this->assertContains('HRD Manager', $templates);
        $this->assertContains('Kasir ATK', $templates);
        $this->assertContains('Kasir Wash', $templates);
        $this->assertContains('Karyawan Wash', $templates);
        $this->assertContains('Customer', $templates);
    }
}
