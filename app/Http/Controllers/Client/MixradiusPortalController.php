<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Ticket;
use App\Services\GenieACSService;
use Illuminate\Support\Facades\Auth;

class MixradiusPortalController extends Controller
{
    public function index(GenieACSService $genie)
    {
        return redirect()->route('client.onu-wifi.show');
    }
}
