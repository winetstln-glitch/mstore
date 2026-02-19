<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Services\GenieACSService;

class DashboardController extends Controller
{
    public function __invoke(GenieACSService $genie)
    {
        $user = Auth::user();
        // Status koneksi sederhana (stub): aktif jika user aktif
        $connectionStatus = $user->is_active ? 'Active' : 'Inactive';
        $customer = Customer::where('user_id', $user->id)->first();
        $deviceSummary = null;
        if ($customer && $customer->genieacs_device_id) {
            $device = $genie->getDeviceDetails($customer->genieacs_device_id);
            if ($device) {
                $ssid = $genie->getWlanSettings($customer->genieacs_device_id, 1, $device)['ssid'] ?? null;
                $ip = $genie->getIpAddress($device);
                $deviceSummary = [
                    'id' => $customer->genieacs_device_id,
                    'ip' => $ip,
                    'ssid' => $ssid,
                ];
            }
        }
        return view('client.dashboard', compact('user', 'connectionStatus', 'deviceSummary'));
    }
}
