<?php

namespace Modules\Network\Events\Infrastructure;

use App\Models\Customer;
use Exception;
use Illuminate\Queue\SerializesModels;

class ProvisioningFailed
{
    use SerializesModels;

    public function __construct(public Customer $customer, public string $action, public Exception $exception) {}
}
