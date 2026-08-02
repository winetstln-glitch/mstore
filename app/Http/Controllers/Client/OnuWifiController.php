<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\GenieACSService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OnuWifiController extends Controller
{
    protected GenieACSService $genieAcs;
    protected WhatsAppService $whatsApp;

    public function __construct(GenieACSService $genieAcs, WhatsAppService $whatsApp)
    {
        $this->genieAcs = $genieAcs;
        $this->whatsApp = $whatsApp;
    }

    /**
     * FALLBACK AUTO-SYNC: Jika customer TIDAK PUNYA record GenieDeviceStatus
     * tapi device di GenieACS ADA (cocok via ont_sn ATAU pppoe_user), maka
     * AUTO BUAT record GenieDeviceStatus BARU + link ke customer_id.
     * Sehingga customer tidak perlu menunggu sync job scheduler jalan.
     */
    private function findOrCreateGenieStatusForCustomer($customer): ?\App\Models\GenieDeviceStatus
    {
        $existing = $customer->genieStatus;
        if ($existing !== null && ! empty($existing->onu_serial)) {
            try {
                $lastInform = $existing->last_inform ? $existing->last_inform->timestamp : 0;
                $isOnline = (time() - $lastInform) < 300;
                if ($existing->is_online !== $isOnline) {
                    $existing->is_online = $isOnline;
                    $existing->timestamps = false;
                    $existing->saveQuietly();
                }
            } catch (\Throwable $e) {
            }
            return $existing;
        }

        $device = null;
        $serial = null;
        $isOnline = false;
        $lastInformAt = null;
        $tr069Ip = null;
        $connectionRequestUrl = null;

        try {
            if (! empty($customer->ont_sn)) {
                $device = $this->genieAcs->findDeviceBySerial($customer->ont_sn);
            }
            if (! $device && ! empty($customer->onu_serial)) {
                $device = $this->genieAcs->findDeviceBySerial($customer->onu_serial);
            }
            if (! $device && ! empty($customer->pppoe_user)) {
                $device = $this->genieAcs->findDeviceByPppoeUsername($customer->pppoe_user);
            }

            if ($device) {
                $serial = data_get($device, '_deviceId._SerialNumber');
                if (empty($serial)) {
                    $serial = data_get($device, '_id');
                }
                $lastInformRaw = data_get($device, '_lastInform');
                if ($lastInformRaw) {
                    try {
                        $lastInformAt = \Illuminate\Support\Carbon::parse($lastInformRaw);
                        $isOnline = (time() - $lastInformAt->timestamp) < 300;
                    } catch (\Throwable $e) {
                        $lastInformAt = null;
                        $isOnline = false;
                    }
                }
                $tr069Ip = data_get($device, '_lastConnectedIp') ?? data_get($device, '_ip');
                $connectionRequestUrl = data_get($device, '_deviceId._ConnectionRequestURL') ?? data_get($device, 'InternetGatewayDevice.ManagementServer.ConnectionRequestURL._value');

                if (! empty($serial)) {
                    $created = \App\Models\GenieDeviceStatus::updateOrCreate(
                        [
                            'onu_serial' => $serial,
                        ],
                        [
                            'customer_id' => $customer->id,
                            'is_online' => $isOnline,
                            'last_inform' => $lastInformAt,
                            'tr069_ip' => is_string($tr069Ip) ? substr($tr069Ip, 0, 45) : null,
                            'connection_request_url' => is_string($connectionRequestUrl) ? substr($connectionRequestUrl, 0, 500) : null,
                        ]
                    );

                    try {
                        if (empty($customer->ont_sn)) {
                            $customer->timestamps = false;
                            $customer->update(['ont_sn' => $serial]);
                        }
                    } catch (\Throwable $e) {
                    }

                    \Illuminate\Support\Facades\Log::info('Auto-create GenieDeviceStatus for Customer', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'matched_by' => ! empty($customer->ont_sn) ? 'ont_sn' : ((! empty($customer->pppoe_user) && ! $device) ? '' : 'pppoe_user'),
                        'onu_serial' => $serial,
                        'is_online' => $isOnline,
                    ]);

                    return $created;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('findOrCreateGenieStatusForCustomer error', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function show()
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (! $customer) {
            return redirect()->route('client.onu-wifi.show')
                ->with('error', 'Data pelanggan tidak ditemukan.');
        }

        // Fallback sync: jika User.phone kosong, ambil dari Customer.phone (WAJIB untuk OTP)
        $registeredWa = $this->ensureUserPhoneSynced($user, $customer);
        $registeredWaDisplay = $this->formatPhoneForDisplay($registeredWa);
        $registeredWaMasked = $registeredWa ? $this->maskPhone($registeredWa) : null;

        $genieStatus = $this->findOrCreateGenieStatusForCustomer($customer);
        $onuSerial = $genieStatus?->onu_serial;
        $isOnline = $genieStatus?->is_online ?? false;
        $lastInform = $genieStatus?->last_inform;

        $ownershipCheck = null;
        $ownershipBlocked = false;
        $ownershipWarning = null;

        $wlan2G = null;
        $wlan5G = null;
        $deviceDetails = null;
        $has5Ghz = false;

        if ($onuSerial) {
            $ownershipCheck = $this->genieAcs->verifyDeviceOwnership($onuSerial, [
                'pppoe_user' => $customer->pppoe_user,
                'customer_id' => $customer->id,
                'wan_mac' => $customer->wan_mac ?? null,
            ]);

            if (! empty($ownershipCheck['error'])) {
                $ownershipWarning = $ownershipCheck['error'] . ' Perubahan WiFi dinonaktifkan sampai masalah ini teratasi.';
                $ownershipBlocked = true;
            } elseif ($ownershipCheck['verified'] === false) {
                $mismatchDetected = [];
                if ($ownershipCheck['checks']['pppoe_username_match'] === false) {
                    $mismatchDetected[] = 'Username PPPoE di perangkat tidak sesuai dengan data pelanggan';
                }
                if ($ownershipCheck['checks']['tag_customer_id_match'] === false) {
                    $mismatchDetected[] = 'Tag pelanggan di perangkat tidak sesuai';
                }
                $ownershipWarning = '⚠️ Verifikasi kepemilikan perangkat tidak meyakinkan (poin verifikasi: ' . $ownershipCheck['level'] . '/4). '
                    . (count($mismatchDetected) ? implode(', ', $mismatchDetected) . '. ' : '')
                    . 'Untuk keamanan Anda, perubahan WiFi DIBLOKIR. Silakan hubungi CS ISP kami untuk verifikasi manual data ONU.';
                $ownershipBlocked = true;

                Log::warning('OnuWifi Ownership Mismatch (show)', [
                    'user_id' => $user->id,
                    'customer_id' => $customer->id,
                    'onu_serial' => $onuSerial,
                    'ownership_level' => $ownershipCheck['level'],
                    'checks' => $ownershipCheck['checks'],
                    'expected_pppoe_user' => $customer->pppoe_user,
                    'device_pppoe_username' => $ownershipCheck['device_pppoe_username'] ?? null,
                ]);
            } elseif ($ownershipCheck['level'] === 1) {
                $ownershipWarning = 'ℹ️ Perangkat berhasil terverifikasi namun dengan data terbatas (poin verifikasi: 1/4). Disarankan hubungi CS agar data PPPoE perangkat disinkronkan.';
            }

            if (! $ownershipBlocked) {
                $deviceDetails = $this->genieAcs->getDeviceDetails($onuSerial);
                if ($deviceDetails) {
                    $wlan2G = $this->genieAcs->getWlanSettings($onuSerial, 1, $deviceDetails);
                    $wlan5G = $this->genieAcs->getWlanSettings($onuSerial, 2, $deviceDetails);
                    $has5Ghz = ! empty($wlan5G) && ! empty($wlan5G['ssid']);
                }
            }
        }

        return view('client.onu-wifi', compact(
            'customer',
            'genieStatus',
            'onuSerial',
            'isOnline',
            'lastInform',
            'wlan2G',
            'wlan5G',
            'has5Ghz',
            'deviceDetails',
            'ownershipCheck',
            'ownershipBlocked',
            'ownershipWarning',
            'registeredWa',
            'registeredWaDisplay',
            'registeredWaMasked'
        ));
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'account_password' => ['required', 'string'],
        ], [
            'account_password.required' => 'Password akun MSTORE wajib diisi untuk verifikasi.',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->account_password, $user->password)) {
            throw ValidationException::withMessages([
                'account_password' => 'Password akun MSTORE salah.',
            ]);
        }

        $customer = $user->customer;
        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Data pelanggan tidak ditemukan.'], 404);
        }

        // Fallback sinkronisasi: jika User.phone kosong coba dari Customer.phone, NORMALISASI ke 62 format
        $phone = $this->ensureUserPhoneSynced($user, $customer);
        if (! $phone) {
            $waCustRaw = $customer->phone;
            $waUserRaw = $user->phone;
            $hint = '';
            if ($waCustRaw || $waUserRaw) {
                $hint = ' Nomor yang tersimpan: ' . trim(($waUserRaw ?? '') . ' ' . ($waCustRaw ?? '')) . '. Format nomor tidak valid, seharusnya diawali 08xx atau 628xx.';
            }
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp tidak terdaftar di akun Anda.' . $hint . ' Silakan hubungi CS / admin ISP kami untuk menambahkan nomor HP aktif ke data pelanggan Anda.',
            ], 400);
        }

        $throttleKey = 'onu_wifi_otp_send_' . $user->id;
        if (Cache::has($throttleKey)) {
            $remaining = Cache::get($throttleKey) - time();
            return response()->json([
                'success' => false,
                'message' => 'Terlalu sering meminta OTP. Silakan tunggu ' . max(1, $remaining) . ' detik lagi.',
            ], 429);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpKey = 'onu_wifi_otp_' . $user->id;
        Cache::put($otpKey, $otp, now()->addMinutes(5));
        Cache::put($throttleKey, time() + 60, 60);

        $message = "*KODE OTP GANTI PASSWORD WiFi*\n\n";
        $message .= "Halo " . ($user->name ?? $customer->name ?? 'Pelanggan') . ",\n";
        $message .= "Kode OTP Anda untuk mengubah password WiFi ONU:\n\n";
        $message .= "```" . $otp . "```\n\n";
        $message .= " Berlaku 5 MENIT. JANGAN BAGIKAN kode ini kepada SIAPAPUN termasuk pihak yang mengaku dari ISP kami.\n";
        $message .= "\nJika Anda tidak merasa meminta kode ini, silakan abaikan pesan ini.";

        $result = $this->whatsApp->sendMessage($phone, $message, 'security_otp', $customer->id);

        if (! ($result['success'] ?? false)) {
            Log::warning('OnuWifi OTP Send Failed for user ' . $user->id . ': ' . ($result['message'] ?? 'unknown'));
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP ke WhatsApp. Silakan coba lagi nanti.',
            ], 502);
        }

        Log::info('OnuWifi OTP Sent', ['user_id' => $user->id, 'customer_id' => $customer->id]);

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil dikirim ke WhatsApp ' . $this->maskPhone($phone) . '. Berlaku 5 menit.',
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ssid_2g' => ['nullable', 'string', 'max:32'],
            'password_2g' => ['nullable', 'string', 'min:8', 'max:63'],
            'ssid_5g' => ['nullable', 'string', 'max:32'],
            'password_5g' => ['nullable', 'string', 'min:8', 'max:63'],
            'same_password' => ['nullable', 'boolean'],
            'account_password' => ['required', 'string'],
            'otp' => ['required', 'string', 'digits:6'],
        ], [
            'password_2g.min' => 'Password WiFi 2.4GHz minimal 8 karakter.',
            'password_2g.max' => 'Password WiFi 2.4GHz maksimal 63 karakter.',
            'password_5g.min' => 'Password WiFi 5GHz minimal 8 karakter.',
            'password_5g.max' => 'Password WiFi 5GHz maksimal 63 karakter.',
            'ssid_2g.max' => 'Nama SSID 2.4GHz maksimal 32 karakter.',
            'ssid_5g.max' => 'Nama SSID 5GHz maksimal 32 karakter.',
            'account_password.required' => 'Password akun MSTORE wajib diisi.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus 6 digit angka.',
        ]);

        $user = Auth::user();
        $customer = $user->customer;

        if (! $customer) {
            return redirect()->route('client.onu-wifi.show')
                ->with('error', 'Data pelanggan tidak ditemukan.');
        }

        if (! Hash::check($data['account_password'], $user->password)) {
            return back()->withErrors(['account_password' => 'Password akun MSTORE salah.'])->withInput();
        }

        $otpKey = 'onu_wifi_otp_' . $user->id;
        $cachedOtp = Cache::get($otpKey);
        if (! $cachedOtp || ! hash_equals($cachedOtp, $data['otp'])) {
            return back()->withErrors(['otp' => 'Kode OTP salah atau sudah kadaluarsa.'])->withInput();
        }

        if (empty($data['ssid_2g']) && empty($data['password_2g']) && empty($data['ssid_5g']) && empty($data['password_5g'])) {
            return back()->withErrors(['ssid_2g' => 'Setidaknya isi SSID atau Password untuk salah satu frekuensi (2.4GHz / 5GHz).'])->withInput();
        }

        if (! empty($data['same_password']) && ! empty($data['password_2g'])) {
            $data['password_5g'] = $data['password_2g'];
            if (empty($data['ssid_5g']) && ! empty($data['ssid_2g'])) {
                $data['ssid_5g'] = $data['ssid_2g'] . '_5G';
            }
        }

        $genieStatus = $this->findOrCreateGenieStatusForCustomer($customer);
        $onuSerial = $genieStatus?->onu_serial;
        if (! $onuSerial) {
            return back()->with('error', 'Nomor Serial ONU tidak ditemukan untuk pelanggan ini. Silakan hubungi admin.')->withInput();
        }

        $ownershipCheck = $this->genieAcs->verifyDeviceOwnership($onuSerial, [
            'pppoe_user' => $customer->pppoe_user,
            'customer_id' => $customer->id,
            'wan_mac' => $customer->wan_mac ?? null,
        ]);

        if (! empty($ownershipCheck['error']) || $ownershipCheck['verified'] === false) {
            Log::warning('OnuWifi Update BLOCKED due to ownership check failed', [
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'onu_serial' => $onuSerial,
                'ownership_error' => $ownershipCheck['error'] ?? null,
                'ownership_level' => $ownershipCheck['level'] ?? 0,
                'checks' => $ownershipCheck['checks'] ?? [],
                'ip' => $request->ip(),
            ]);
            $msg = ! empty($ownershipCheck['error']) ? $ownershipCheck['error'] : 'Verifikasi kepemilikan perangkat gagal (poin verifikasi: ' . ($ownershipCheck['level'] ?? 0) . '/4). ';
            $msg .= 'Perubahan WiFi DIBLOKIR untuk keamanan akun Anda. Silakan hubungi CS ISP kami untuk verifikasi manual data ONU.';
            return back()->with('error', $msg)->withInput();
        }

        $updateData = [];
        if (! empty($data['ssid_2g'])) {
            $updateData['ssid_2g'] = trim($data['ssid_2g']);
        }
        if (! empty($data['password_2g'])) {
            $updateData['password_2g'] = trim($data['password_2g']);
        }
        if (! empty($data['ssid_5g'])) {
            $updateData['ssid_5g'] = trim($data['ssid_5g']);
        }
        if (! empty($data['password_5g'])) {
            $updateData['password_5g'] = trim($data['password_5g']);
        }

        Log::info('OnuWifi Update Attempt', [
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'onu_serial' => $onuSerial,
            'ownership_level' => $ownershipCheck['level'],
            'ownership_checks' => $ownershipCheck['checks'],
            'ip' => $request->ip(),
            'keys' => array_keys($updateData),
        ]);

        $result = $this->genieAcs->updateWlanSettings($onuSerial, $updateData);

        if (! ($result['success'] ?? false)) {
            Log::error('OnuWifi Update Failed', [
                'user_id' => $user->id,
                'onu_serial' => $onuSerial,
                'message' => $result['message'] ?? 'unknown',
            ]);
            return back()->with('error', 'Gagal mengubah pengaturan WiFi: ' . ($result['message'] ?? 'Kesalahan tidak diketahui'))->withInput();
        }

        Cache::forget($otpKey);

        Log::info('OnuWifi Update Success', [
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'onu_serial' => $onuSerial,
            'status' => $result['status'] ?? 'unknown',
        ]);

        $phone = $this->ensureUserPhoneSynced($user, $customer);
        if ($phone && (! empty($updateData['password_2g']) || ! empty($updateData['password_5g']))) {
            $infoLines = [];
            if (! empty($updateData['ssid_2g'])) $infoLines[] = "• SSID 2.4GHz: " . $updateData['ssid_2g'];
            if (! empty($updateData['password_2g'])) $infoLines[] = "• Password 2.4GHz: " . $updateData['password_2g'];
            if (! empty($updateData['ssid_5g'])) $infoLines[] = "• SSID 5GHz: " . $updateData['ssid_5g'];
            if (! empty($updateData['password_5g'])) $infoLines[] = "• Password 5GHz: " . $updateData['password_5g'];

            $confirmMsg = "*✅ PASSWORD WiFi BERHASIL DIUBAH*\n\n";
            $confirmMsg .= "Halo " . ($user->name ?? $customer->name ?? 'Pelanggan') . ",\n";
            $confirmMsg .= "Pengaturan WiFi ONU Anda berhasil diupdate.\n\n";
            $confirmMsg .= implode("\n", $infoLines) . "\n\n";
            if (($result['status'] ?? '') === 'queued') {
                $confirmMsg .= "ℹ️ ONU Anda sedang offline. Perubahan akan otomatis diterapkan saat ONU online kembali (maks 1 jam kedepan).\n";
            } else {
                $confirmMsg .= "⚠️ SEMUA PERANGKAT yang terhubung akan TERPUTUS dan harus connect ulang menggunakan password baru.\n";
            }
            $confirmMsg .= "\nSimpan baik-baik password baru Anda.";

            $this->whatsApp->sendMessage($phone, $confirmMsg, 'wifi_password_changed', $customer->id);
        }

        $statusMsg = ($result['status'] ?? '') === 'queued'
            ? 'Pengaturan WiFi berhasil dikirim ke antrian. ONU Anda sedang offline, perubahan akan berlaku saat ONU online kembali (maks 1 jam).'
            : 'Pengaturan WiFi berhasil diubah! Semua perangkat terhubung akan terputus, silakan connect ulang dengan password baru.';

        return redirect()->route('client.onu-wifi.show')->with('status', $statusMsg);
    }

    protected function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) <= 6) return $phone;
        $prefix = substr($digits, 0, 3);
        $suffix = substr($digits, -2);
        $masked = str_repeat('*', max(1, strlen($digits) - 5));
        return $prefix . $masked . $suffix;
    }

    /**
     * Normalisasi nomor HP ke format 62xxxxxxxxxx (yang dipakai WhatsApp).
     */
    protected function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (! is_string($digits) || $digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }
        if (strlen($digits) < 10) {
            return null;
        }
        return $digits;
    }

    /**
     * Fallback jika User->phone KOSONG tapi Customer->phone ADA:
     * otomatis sync ke User->phone agar tidak perlu input ulang.
     */
    protected function ensureUserPhoneSynced($user, $customer): ?string
    {
        $userPhoneRaw = $user->phone ?? null;
        $customerPhoneRaw = $customer->phone ?? null;
        $userPhone = $this->normalizePhone($userPhoneRaw);
        $customerPhone = $this->normalizePhone($customerPhoneRaw);

        $finalPhone = $userPhone ?? $customerPhone;

        if (! $userPhone && $customerPhone && $user->exists) {
            try {
                $user->timestamps = false;
                $user->updateQuietly(['phone' => $customerPhone]);
            } catch (\Throwable $e) {
                Log::warning('ensureUserPhoneSynced gagal update user phone: ' . $e->getMessage(), ['user_id' => $user->id]);
            }
        }

        return $finalPhone;
    }

    /**
     * Format nomor WA tampil ke user: 62812... → 0812-xxxx-xxxx (mudah dibaca).
     */
    protected function formatPhoneForDisplay(?string $phone): ?string
    {
        $digits = $this->normalizePhone($phone);
        if (! $digits) return null;
        $without62 = preg_replace('/^62/', '0', $digits);
        if (strlen($without62) < 10) return $without62;
        // Contoh: 0812-3456-7890
        return preg_replace('/^(\d{4})(\d{4})(\d+)$/', '$1-$2-$3', $without62);
    }
}
