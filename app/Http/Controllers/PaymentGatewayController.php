<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class PaymentGatewayController extends Controller
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function dashboard()
    {
        $gateways = $this->paymentManager->all();
        $stats = $this->getTransactionStats();
        
        $gatewayStatuses = [];
        foreach ($gateways as $id => $gateway) {
            $lastSync = Setting::getValue("payment_{$id}_last_sync");
            $gatewayStatuses[$id] = [
                'name' => $gateway->getName(),
                'id' => $id,
                'is_sandbox' => (bool) Setting::getValue("{$id}_sandbox", true),
                'last_sync' => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : 'Never',
                'merchant_code' => Setting::getValue("{$id}_merchant_code") ?: Setting::getValue("{$id}_client_id", '-'),
            ];
        }

        return view('payment_gateway.dashboard', compact('gatewayStatuses', 'stats'));
    }

    public function gateway(string $gatewayId)
    {
        $gateway = $this->paymentManager->gateway($gatewayId);
        $settings = Setting::where('group', 'payment_gateway')
            ->where('key', 'like', "{$gatewayId}_%")
            ->get();

        return view('payment_gateway.gateway', [
            'gateway' => $gateway,
            'gatewayId' => $gatewayId,
            'settings' => $settings,
            'callbackUrl' => route('webhooks.payment.callback'),
            'returnUrl' => route('webhooks.payment.return'),
        ]);
    }

    public function update(Request $request, string $gatewayId)
    {
        $gateway = $this->paymentManager->gateway($gatewayId);
        $data = $request->except(['_token']);

        // Audit Log
        $oldValues = [];
        foreach ($data as $key => $value) {
            $settingKey = "{$gatewayId}_{$key}";
            $oldValues[$settingKey] = Setting::getValue($settingKey);
        }

        $gateway->saveConfig($data);

        // Update last sync time
        Setting::updateOrCreate(
            ['key' => "payment_{$gatewayId}_last_sync"],
            ['value' => now()->toDateTimeString(), 'group' => 'payment_gateway']
        );

        AuditLog::log(
            "Update {$gateway->getName()} Configuration",
            null,
            $oldValues,
            $data,
            "User " . auth()->user()->name . " updated {$gateway->getName()} payment gateway settings."
        );

        return redirect()->back()->with('success', "{$gateway->getName()} settings updated successfully.");
    }

    public function testConnection(string $gatewayId)
    {
        try {
            $gateway = $this->paymentManager->gateway($gatewayId);
            $result = $gateway->testConnection();

            if ($result['success']) {
                Setting::updateOrCreate(
                    ['key' => "payment_{$gatewayId}_status"],
                    ['value' => 'connected', 'group' => 'payment_gateway']
                );
            } else {
                Setting::updateOrCreate(
                    ['key' => "payment_{$gatewayId}_status"],
                    ['value' => 'error', 'group' => 'payment_gateway']
                );
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function getTransactionStats()
    {
        $today = Carbon::today();
        
        return [
            'today_total' => Transaction::whereDate('created_at', $today)->count(),
            'today_amount' => Transaction::whereDate('created_at', $today)->where('status', 'paid')->sum('amount'),
            'pending' => Transaction::where('status', 'pending')->count(),
            'paid' => Transaction::where('status', 'paid')->count(),
            'failed' => Transaction::where('status', 'failed')->count(),
            'success_rate' => $this->calculateSuccessRate(),
        ];
    }

    protected function calculateSuccessRate()
    {
        $total = Transaction::whereIn('status', ['paid', 'failed'])->count();
        if ($total === 0) return 0;
        
        $paid = Transaction::where('status', 'paid')->count();
        return round(($paid / $total) * 100, 2);
    }
}
