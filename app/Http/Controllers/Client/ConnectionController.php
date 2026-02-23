<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\GenieACSService;
use Illuminate\Support\Facades\Auth;

class ConnectionController extends Controller
{
    public function index(GenieACSService $genie)
    {
        $user = Auth::user();
        $customer = Customer::where('user_id', $user->id)->first();

        $device = null;
        $ip = null;
        $pppoeUsername = null;
        $pppoeIp = null;
        $lastInform = null;
        $serial = null;
        $productClass = null;
        $uptime = null;

        if ($customer && $customer->genieacs_device_id) {
            $device = $genie->getDeviceDetails($customer->genieacs_device_id);
            if (is_array($device)) {
                $ip = $genie->getIpAddress($device);
                $pppoeUsername = $device['VirtualParameters']['pppoeUsername'] ?? $device['VirtualParameters']['pppoeUsername2'] ?? null;
                $pppoeIp = $device['VirtualParameters']['pppoeIP'] ?? null;
                $lastInform = $device['_lastInform'] ?? null;
                $serial = $device['_deviceId']['_SerialNumber'] ?? null;
                $productClass = $device['_deviceId']['_ProductClass'] ?? null;
                $uptime = $device['VirtualParameters']['getdeviceuptime'] ?? null;
            }
        }

        $connected = false;
        if ($ip || $pppoeIp) {
            $connected = true;
        } elseif ($lastInform) {
            try {
                $t = strtotime($lastInform);
                if ($t && (time() - $t) < 15 * 60) {
                    $connected = true;
                }
            } catch (\Throwable $e) {
            }
        }

        $activeSince = $customer?->created_at;

        return view('client.connection', compact(
            'connected',
            'activeSince',
            'ip',
            'pppoeIp',
            'pppoeUsername',
            'serial',
            'productClass',
            'uptime',
            'customer',
            'user'
        ));
    }
}
