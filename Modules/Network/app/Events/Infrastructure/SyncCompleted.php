<?php

namespace Modules\Network\Events\Infrastructure;

use App\Models\Customer;
use Illuminate\Queue\SerializesModels;

class SyncCompleted
{
    use SerializesModels;

    public function __construct(public Customer $customer) {}
}
