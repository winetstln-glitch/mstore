<?php

namespace Modules\Network\Events\Domain;

use App\Models\Customer;
use Illuminate\Queue\SerializesModels;

class CustomerUnsuspended
{
    use SerializesModels;

    public function __construct(public Customer $customer) {}
}
