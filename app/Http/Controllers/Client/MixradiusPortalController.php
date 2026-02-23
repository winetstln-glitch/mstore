<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use App\Services\GenieACSService;
use App\Models\Ticket;

class MixradiusPortalController extends Controller
{
    public function index(GenieACSService $genie)
    {
        $user = Auth::user();
        $customer = Customer::where('user_id', $user->id)->first();

        $invoices = $user->invoices()->latest()->get();
        $currentInvoice = $invoices->first();
        $dueInvoice = $user->invoices()->where('status', 'pending')->orderBy('due_date')->first();
        $totalDue = $user->invoices()->where('status', 'pending')->sum('amount');
        $devicesCount = $customer ? Device::where('customer_id', $customer->id)->count() : 0;

        $dueSeconds = null;
        if ($dueInvoice && $dueInvoice->due_date) {
            $dueSeconds = now()->diffInSeconds($dueInvoice->due_date, false);
        }

        $ip = null;
        $pppoeIp = null;
        $pppoeUsername = null;
        $deviceMac = null;
        $uptime = null;
        $dataUsage = null;
        if ($customer && $customer->genieacs_device_id) {
            $device = $genie->getDeviceDetails($customer->genieacs_device_id);
            if (is_array($device)) {
                $ip = $genie->getIpAddress($device);
                $pppoeUsername = $device['VirtualParameters']['pppoeUsername'] ?? $device['VirtualParameters']['pppoeUsername2'] ?? null;
                $pppoeIp = $device['VirtualParameters']['pppoeIP'] ?? null;
                $deviceMac = $device['VirtualParameters']['pppoeMac'] ?? $device['VirtualParameters']['PonMac'] ?? null;
                $uptime = $device['VirtualParameters']['getdeviceuptime'] ?? null;
            }
        }

        $recentInvoices = $user->invoices()->latest()->take(10)->get();
        $recentTickets = $customer ? Ticket::where('customer_id', $customer->id)->latest()->take(10)->get() : collect();
        $activeSince = $customer?->created_at;

        return view('client.portal', compact(
            'user',
            'currentInvoice',
            'totalDue',
            'devicesCount',
            'dueInvoice',
            'dueSeconds',
            'ip',
            'pppoeIp',
            'pppoeUsername',
            'deviceMac',
            'uptime',
            'recentInvoices',
            'recentTickets',
            'activeSince',
            'dataUsage'
        ));
    }
}
