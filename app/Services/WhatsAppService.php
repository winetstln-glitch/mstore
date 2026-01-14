<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WhatsAppService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        // Configure these in .env
        $this->apiKey = config('services.whatsapp.key');
        $this->baseUrl = config('services.whatsapp.url');
    }

    /**
     * Send Message
     */
    public function sendMessage($phone, $message, $category = 'general', $customerId = null)
    {
        // 1. Log to DB first
        $logId = DB::table('notification_logs')->insertGetId([
            'customer_id' => $customerId,
            'target_phone' => $phone,
            'type' => 'whatsapp',
            'category' => $category,
            'message' => $message,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Send to API (Example using a generic POST structure)
        if ($this->baseUrl && $this->apiKey) {
            try {
                $response = Http::post($this->baseUrl . '/send-message', [
                    'api_key' => $this->apiKey,
                    'phone' => $phone,
                    'message' => $message,
                ]);

                DB::table('notification_logs')->where('id', $logId)->update([
                    'status' => $response->successful() ? 'sent' : 'failed',
                    'response' => $response->body()
                ]);

                return $response->successful();
            } catch (\Exception $e) {
                Log::error("WhatsApp Error: " . $e->getMessage());
                DB::table('notification_logs')->where('id', $logId)->update([
                    'status' => 'failed',
                    'response' => $e->getMessage()
                ]);
                return false;
            }
        }

        // If no API configured, just leave as pending (or mark sent if simulating)
        return true;
    }

    public function sendInvoice(Customer $customer, $invoice)
    {
        $message = "Halo {$customer->name},\n\nTagihan internet Anda bulan ini sebesar Rp " . number_format($invoice->amount, 0, ',', '.') . " telah terbit.\nJatuh tempo: {$invoice->due_date->format('d-m-Y')}.\n\nMohon segera lakukan pembayaran.";
        return $this->sendMessage($customer->phone, $message, 'invoice', $customer->id);
    }

    public function sendPaymentSuccess(Customer $customer, $invoice)
    {
        $message = "Terima kasih {$customer->name},\nPembayaran tagihan sebesar Rp " . number_format($invoice->amount, 0, ',', '.') . " telah kami terima.\nLayanan internet Anda aktif.";
        return $this->sendMessage($customer->phone, $message, 'payment', $customer->id);
    }

    public function sendIsolationNotification(Customer $customer)
    {
        $message = "Halo {$customer->name},\nLayanan internet Anda sementara kami ISOLIR karena belum melakukan pembayaran.\nMohon segera lunasi tagihan Anda agar layanan kembali normal.";
        return $this->sendMessage($customer->phone, $message, 'isolate', $customer->id);
    }
    
    public function broadcastMessage($area, $message)
    {
        // Logic to find customers in area/odp
        $customers = Customer::where('odp', 'LIKE', "%$area%")->get();
        $count = 0;
        foreach ($customers as $customer) {
            if ($this->sendMessage($customer->phone, $message, 'broadcast', $customer->id)) {
                $count++;
            }
        }
        return $count;
    }
}
