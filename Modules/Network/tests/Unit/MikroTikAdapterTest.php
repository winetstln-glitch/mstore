<?php

namespace Modules\Network\Tests\Unit;

use Tests\TestCase;
use Modules\Network\Adapters\MikroTikAdapter;
use Modules\Network\Contracts\NetworkProviderInterface;
use App\Models\Customer;
use App\Models\Router;
use App\Services\MikrotikService;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MikroTikAdapterTest extends TestCase
{
    use RefreshDatabase;

    protected MikroTikAdapter $adapter;
    protected Customer $customer;
    protected Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new MikroTikAdapter();
        $this->router = Router::factory()->create([
            'host' => '192.168.88.1',
            'username' => 'admin',
            'password' => 'password',
        ]);
        $this->customer = Customer::factory()->create([
            'pppoe_user' => 'test_user',
            'pppoe_password' => 'test_pass',
            'package' => 'default',
            'router_id' => $this->router->id,
            'status' => 'active',
        ]);
    }

    public function test_adapter_implements_correct_interface(): void
    {
        $this->assertInstanceOf(NetworkProviderInterface::class, $this->adapter);
    }
}
