<?php

namespace Modules\Network\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\Customer;

class ProvisioningCompleted
{
    use SerializesModels;

    public function __construct(public Customer $customer, public string $action)
    {
    }
}
