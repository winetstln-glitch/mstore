<?php

namespace Modules\Network\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\Customer;

class CustomerCreated
{
    use SerializesModels;

    public function __construct(public Customer $customer)
    {
    }
}
