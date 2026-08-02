<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\GenieACSService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(GenieACSService $genie)
    {
        return redirect()->route('client.onu-wifi.show');
    }
}
