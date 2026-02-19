<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MixRadiusService
{
    protected string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('MIXRADIUS_BASE_URL', ''), '/');
        $this->token = env('MIXRADIUS_API_TOKEN');
    }

    // Alur: Setelah pembayaran sukses, sistem memanggil endpoint MixRADIUS
    // untuk memperbarui masa aktif layanan di FreeRADIUS/MikroTik NAS.
    public function renewUser(User $user, ?string $note = null): void
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            return;
        }
        $payload = [
            'action' => 'renew',
            // Gunakan email sebagai identitas; sesuaikan jika Anda menyimpan PPPoE/Hotspot username di kolom lain
            'username' => $user->email,
            'note' => $note,
        ];
        Http::withToken($this->token)
            ->acceptJson()
            ->post($this->baseUrl . '/api/users/renew', $payload)
            ->throw();
    }

    public function changePassword(User $user, string $newPassword): bool
    {
        $endpoint = 'http://mixradius.local/api/user/update';
        $payload = ['username' => $user->email, 'password' => $newPassword];
        try {
            $response = Http::timeout(8)->acceptJson()->post($endpoint, $payload);
            if ($response->successful()) {
                return true;
            }
            Log::warning('MixRADIUS changePassword non-2xx', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('MixRADIUS changePassword error', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
