<?php

namespace Tests\Feature\Network;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Network\Events\Domain\CustomerActivated;
use Modules\Network\Events\Domain\CustomerSuspended;
use Modules\Network\Events\Domain\CustomerUnsuspended;
use Tests\TestCase;

class CustomerProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_activation_triggers_provisioning_event(): void
    {
        Event::fake();

        $customer = Customer::create([
            'name' => 'Test Customer',
            'status' => 'suspend',
        ]);

        $customer->update(['status' => 'active']);
        event(new CustomerActivated($customer));

        Event::assertDispatched(CustomerActivated::class);
    }

    public function test_customer_suspension_triggers_suspension_event(): void
    {
        Event::fake();

        $customer = Customer::create([
            'name' => 'Test Customer',
            'status' => 'active',
        ]);

        $customer->update(['status' => 'suspend']);
        event(new CustomerSuspended($customer));

        Event::assertDispatched(CustomerSuspended::class);
    }

    public function test_customer_unsuspension_triggers_unsuspension_event(): void
    {
        Event::fake();

        $customer = Customer::create([
            'name' => 'Test Customer',
            'status' => 'suspend',
        ]);

        $customer->update(['status' => 'active']);
        event(new CustomerUnsuspended($customer));

        Event::assertDispatched(CustomerUnsuspended::class);
    }
}
