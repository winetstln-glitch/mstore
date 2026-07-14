<?php

namespace Modules\Network\Tests\Unit;

use Tests\TestCase;
use Modules\Network\Adapters\DummyAdapter;
use Modules\Network\Contracts\NetworkProviderInterface;
use Modules\Network\DTO\CustomerNetworkDTO;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DummyAdapterTest extends TestCase
{
    protected DummyAdapter $adapter;
    protected Customer $customer;
    protected CustomerNetworkDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new DummyAdapter();
        $this->customer = Customer::factory()->create([
            'pppoe_user' => 'test_user',
            'pppoe_password' => 'test_pass',
            'package' => 'default',
            'status' => 'active',
        ]);
        $this->dto = CustomerNetworkDTO::fromCustomer($this->customer);
    }

    public function test_adapter_implements_correct_interface(): void
    {
        $this->assertInstanceOf(NetworkProviderInterface::class, $this->adapter);
    }

    public function test_activate_customer(): void
    {
        $result = $this->adapter->activateCustomer($this->customer);
        $this->assertTrue($result);
    }

    public function test_suspend_customer(): void
    {
        $result = $this->adapter->suspendCustomer($this->customer);
        $this->assertTrue($result);
    }

    public function test_unsuspend_customer(): void
    {
        $result = $this->adapter->unsuspendCustomer($this->customer);
        $this->assertTrue($result);
    }

    public function test_disconnect_customer(): void
    {
        $result = $this->adapter->disconnectCustomer($this->customer);
        $this->assertTrue($result);
    }

    public function test_health(): void
    {
        $health = $this->adapter->health();
        $this->assertIsArray($health);
        $this->assertArrayHasKey('status', $health);
    }
}
