<?php

namespace Tests\Unit\Network;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Network\Services\ProvisioningService;
use Tests\TestCase;

class ProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'status' => 'suspend',
        ]);

        $service = app(ProvisioningService::class);
        $result = $service->activate($customer);

        $this->assertTrue($result);
        $this->assertEquals('active', $customer->fresh()->status);
    }

    public function test_suspend_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'status' => 'active',
        ]);

        $service = app(ProvisioningService::class);
        $result = $service->suspend($customer);

        $this->assertTrue($result);
        $this->assertEquals('suspend', $customer->fresh()->status);
    }

    public function test_unsuspend_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'status' => 'suspend',
        ]);

        $service = app(ProvisioningService::class);
        $result = $service->unsuspend($customer);

        $this->assertTrue($result);
        $this->assertEquals('active', $customer->fresh()->status);
    }

    public function test_idempotent_activation(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'status' => 'active',
        ]);

        $service = app(ProvisioningService::class);
        $result = $service->activate($customer);

        $this->assertTrue($result);
        $this->assertEquals('active', $customer->fresh()->status);
    }
}
