<?php

namespace Modules\Network\Events\Infrastructure;

use App\Models\Customer;
use Illuminate\Queue\SerializesModels;

class ProvisioningCompleted
{
    use SerializesModels;

    public function __construct(public Customer $customer, public string $action) {}
}
