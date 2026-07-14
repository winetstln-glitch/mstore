<?php

namespace Modules\Network\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\Customer;
use Exception;

class ProvisioningFailed
{
    use SerializesModels;

    public function __construct(public Customer $customer, public string $action, public Exception $exception)
    {
    }
}
