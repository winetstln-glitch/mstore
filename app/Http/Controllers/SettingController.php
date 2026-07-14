<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class SettingController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:setting.view', only: ['index', 'attendance', 'atk', 'wash']),
            new Middleware('permission:setting.update', only: ['update', 'backupDatabase']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->ensureReceiptIdentitySettings();
        $this->ensureAttendanceSettings();

        $settings = Setting::query()
            ->whereNotIn('group', ['telegram', 'whatsapp', 'payment_gateway', 'duitku', 'midtrans'])
            ->where('key', '!=', 'subscription_packages')
            ->where('key', 'not like', 'atk_%')
            ->where('key', 'not like', 'wash_%')
            ->where('key', 'not like', 'payment_%')
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');

        $accountOptions = Account::orderBy('code')->get();

        return view('settings.index', compact('settings', 'accountOptions'));
    }

    public function attendance()
    {
        $this->ensureAttendanceSettings();

        $legacyKeys = [
            'attendance_shift_1_start',
            'attendance_shift_1_end',
            'attendance_shift_2_start',
            'attendance_shift_2_end',
        ];

        $settings = Setting::query()
            ->whereIn('group', ['attendance', 'schedule'])
            ->whereNotIn('key', $legacyKeys)
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->pluck('value', 'key')
            ->all();

        return view('settings.attendance', compact('settings'));
    }

    public function atk()
    {
        $this->ensureReceiptIdentitySettings();

        return view('atk.settings.index');
    }

    public function wash()
    {
        $this->ensureReceiptIdentitySettings();

        return view('wash.settings.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $logoUploads = [
            'store_logo_file' => 'store_logo',
            'brand_gtwash_logo_file' => 'brand_gtwash_logo',
            'brand_mstore_logo_file' => 'brand_mstore_logo',
            'brand_mstorenet_logo_file' => 'brand_mstorenet_logo',
            'atk_store_logo_file' => 'atk_store_logo',
            'wash_store_logo_file' => 'wash_store_logo',
            'wedding_service_1_image_file' => 'wedding_service_1_image',
            'wedding_service_2_image_file' => 'wedding_service_2_image',
            'wedding_service_3_image_file' => 'wedding_service_3_image',
        ];
        $logoClearFlags = [
            'clear_store_logo' => 'store_logo',
            'clear_brand_gtwash_logo' => 'brand_gtwash_logo',
            'clear_brand_mstore_logo' => 'brand_mstore_logo',
            'clear_brand_mstorenet_logo' => 'brand_mstorenet_logo',
            'clear_atk_store_logo' => 'atk_store_logo',
            'clear_wash_store_logo' => 'wash_store_logo',
            'clear_wedding_service_1_image' => 'wedding_service_1_image',
            'clear_wedding_service_2_image' => 'wedding_service_2_image',
            'clear_wedding_service_3_image' => 'wedding_service_3_image',
        ];
        $data = $request->except([
            '_token',
            '_method',
            ...array_keys($logoUploads),
            ...array_keys($logoClearFlags),
        ]);

        $request->validate([
            'store_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'brand_gtwash_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'brand_mstore_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'brand_mstorenet_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'atk_store_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'wash_store_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'wedding_service_1_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'wedding_service_2_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'wedding_service_3_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'clear_store_logo' => 'nullable|boolean',
            'clear_brand_gtwash_logo' => 'nullable|boolean',
            'clear_brand_mstore_logo' => 'nullable|boolean',
            'clear_brand_mstorenet_logo' => 'nullable|boolean',
            'clear_atk_store_logo' => 'nullable|boolean',
            'clear_wash_store_logo' => 'nullable|boolean',
            'clear_wedding_service_1_image' => 'nullable|boolean',
            'clear_wedding_service_2_image' => 'nullable|boolean',
            'clear_wedding_service_3_image' => 'nullable|boolean',
            'pos_printer_auto_reconnect' => 'nullable|in:0,1',
            'pos_print_logo_enabled' => 'nullable|in:0,1',
            'pos_bluetooth_chunk_size' => 'nullable|integer|min:90|max:512',
            'pos_bluetooth_chunk_delay_ms' => 'nullable|integer|min:0|max:100',
            'pos_qris_text' => 'nullable|string|max:2000',
            'pos_preferred_printer_name' => 'nullable|string|max:120',
            'pos_preferred_printer_id' => 'nullable|string|max:120',
            'pos_performance_profile' => 'nullable|in:ultrafast,balanced,stable',
            'pos_printer_type' => 'nullable|in:escpos,tspl',
            'pos_label_width_mm' => 'nullable|integer|min:20|max:200',
            'pos_label_height_mm' => 'nullable|integer|min:20|max:500',
            'pos_label_gap_mm' => 'nullable|integer|min:0|max:50',
            'atk_receipt_title' => 'nullable|string|max:120',
            'atk_receipt_footer_title' => 'nullable|string|max:120',
            'atk_receipt_footer_message' => 'nullable|string|max:500',
            'atk_receipt_footer_note' => 'nullable|string|max:500',
            'atk_receipt_powered_by' => 'nullable|string|max:120',
            'atk_fee_bank_percent' => 'nullable|numeric|min:0',
            'atk_fee_bank_fixed' => 'nullable|numeric|min:0',
            'atk_fee_cashout_percent' => 'nullable|numeric|min:0',
            'atk_fee_cashout_fixed' => 'nullable|numeric|min:0',
            'atk_fee_topup_percent' => 'nullable|numeric|min:0',
            'atk_fee_topup_fixed' => 'nullable|numeric|min:0',
            'atk_fee_ppob_percent' => 'nullable|numeric|min:0',
            'atk_fee_ppob_fixed' => 'nullable|numeric|min:0',
            'wash_receipt_title' => 'nullable|string|max:120',
            'wash_receipt_footer_title' => 'nullable|string|max:120',
            'wash_receipt_footer_message' => 'nullable|string|max:500',
            'wash_receipt_footer_note' => 'nullable|string|max:500',
            'wash_receipt_powered_by' => 'nullable|string|max:120',
            'wash_receipt_holiday_greeting' => 'nullable|string|max:500',
            'wash_loyalty_target' => 'nullable|integer|min:1',
            'wash_holiday_pricing_start_date' => 'nullable|date_format:Y-m-d',
            'wash_holiday_pricing_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:wash_holiday_pricing_start_date',
            'landing_internet_promo_enabled' => 'nullable|in:0,1',
            'landing_internet_promo_percent' => 'nullable|integer|min:0|max:90',
            'landing_internet_promo_label' => 'nullable|string|max:120',
            'brand_gtwash_slogan' => 'nullable|string|max:160',
            'brand_mstore_slogan' => 'nullable|string|max:160',
            'brand_mstorenet_slogan' => 'nullable|string|max:160',
            'whatsapp_attendance_notification_enabled' => 'nullable|in:0,1',
            'whatsapp_attendance_group_id' => 'nullable|string|max:255',
            'telegram_attendance_notification_enabled' => 'nullable|in:0,1',
            'telegram_attendance_group_id' => 'nullable|string|max:255',
            'attendance_photo_required' => 'nullable|in:0,1',
        ]);

        if (array_key_exists('attendance_photo_required', $data)) {
            $data['attendance_enable_photo'] = $data['attendance_photo_required'];
        }

        $existingSettings = Setting::query()
            ->whereIn('key', array_values($logoUploads))
            ->get(['key', 'value', 'group', 'type', 'label'])
            ->keyBy('key');

        foreach ($logoClearFlags as $clearKey => $settingKey) {
            if (! $request->boolean($clearKey)) {
                continue;
            }

            $oldValue = $existingSettings->get($settingKey)?->value ?? '';
            if (is_string($oldValue) && (str_starts_with($oldValue, 'storage/settings-logos/') || str_starts_with($oldValue, 'storage/landing-wedding/'))) {
                $oldPath = str_replace('storage/', '', $oldValue);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $data[$settingKey] = '';
            $existingSettings->put($settingKey, (object) [
                'key' => $settingKey,
                'value' => '',
                'group' => $existingSettings->get($settingKey)?->group,
                'type' => $existingSettings->get($settingKey)?->type,
                'label' => $existingSettings->get($settingKey)?->label,
            ]);
        }

        foreach ($logoUploads as $fileKey => $settingKey) {
            if (! $request->hasFile($fileKey)) {
                continue;
            }

            $oldValue = $existingSettings->get($settingKey)?->value ?? '';
            if (is_string($oldValue) && (str_starts_with($oldValue, 'storage/settings-logos/') || str_starts_with($oldValue, 'storage/landing-wedding/'))) {
                $oldPath = str_replace('storage/', '', $oldValue);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $storedPath = str_starts_with($fileKey, 'wedding_service_')
                ? $request->file($fileKey)->store('landing-wedding', 'public')
                : $request->file($fileKey)->store('settings-logos', 'public');
            $data[$settingKey] = 'storage/'.$storedPath;
            $existingSettings->put($settingKey, (object) [
                'key' => $settingKey,
                'value' => $data[$settingKey],
                'group' => $existingSettings->get($settingKey)?->group,
                'type' => $existingSettings->get($settingKey)?->type,
                'label' => $existingSettings->get($settingKey)?->label,
            ]);
        }

        if ($data !== []) {
            $existingSettings = Setting::query()
                ->whereIn('key', array_keys($data))
                ->get(['key', 'group', 'type', 'label'])
                ->keyBy('key');
        }

        $rows = [];
        $timestamp = now();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if (in_array($key, ['mixradius_api_token', 'tr069_acs_password', 'tr069_connection_request_password']) && ($value === null || $value === '')) {
                continue;
            }

            $existing = $existingSettings->get($key);
            $group = $existing?->group;
            if (str_starts_with($key, 'atk_')) {
                $group = 'atk';
            } elseif (str_starts_with($key, 'wash_')) {
                $group = 'wash';
            } elseif (str_starts_with($key, 'mixradius_')) {
                $group = 'mixradius';
            } elseif (! is_string($group) || $group === '') {
                $group = 'general';
            }
            $rows[] = [
                'key' => $key,
                'value' => $value,
                'group' => $group,
                'type' => $existing?->type ?? ($key === 'mixradius_enforce_customer_login' ? 'boolean' : 'text'),
                'label' => $existing?->label ?? ucwords(str_replace('_', ' ', $key)),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($rows !== []) {
            Setting::upsert(
                $rows,
                ['key'],
                ['value', 'group', 'type', 'label', 'updated_at']
            );
            Setting::forgetCache();
        }

        return redirect()->back()->with('success', __('Settings updated successfully.'));
    }

    public function backupDatabase()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection === 'sqlite') {
            if (! file_exists($database)) {
                return redirect()->back()->with('error', 'Database file not found.');
            }

            $filename = 'backup-'.date('Y-m-d-His').'.sqlite';

            return response()->download($database, $filename);
        }

        if ($connection === 'mysql') {
            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port", 3306);
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");

            $filename = 'backup-'.date('Y-m-d-His').'.sql';
            $tempPath = storage_path('app/'.$filename);

            $arguments = array_values(array_filter([
                'mysqldump',
                "--host={$host}",
                "--port={$port}",
                "--user={$username}",
                $password ? "--password={$password}" : null,
                '--single-transaction',
                '--quick',
                '--lock-tables=false',
                $database,
            ], static fn ($value) => $value !== null && $value !== ''));

            $process = new Process($arguments);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                return redirect()->back()->with('error', 'Backup MySQL gagal. Pastikan mysqldump tersedia di server.');
            }

            if (file_put_contents($tempPath, $process->getOutput()) === false) {
                return redirect()->back()->with('error', 'File backup tidak dapat dibuat.');
            }

            if (! file_exists($tempPath)) {
                return redirect()->back()->with('error', 'File backup tidak dapat dibuat.');
            }

            $response = response()->download($tempPath, $filename);
            $response->deleteFileAfterSend(true);

            return $response;
        }

        return redirect()->back()->with('error', 'Backup for ' . $connection . ' is not supported yet.');
    }

    private function ensureReceiptIdentitySettings(): void
    {
        // Cache this check for 1 hour to prevent constant DB hits
        if (\Illuminate\Support\Facades\Cache::has('settings_ensured')) {
            return;
        }

        $defaults = [
            [
                'key' => 'store_name',
                'value' => config('app.name', 'MStore'),
                'group' => 'general',
                'type' => 'text',
                'label' => 'Nama Toko Umum',
            ],
            [
                'key' => 'store_address',
                'value' => 'Jl. Contoh No. 1',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Alamat Toko Umum',
            ],
            [
                'key' => 'store_phone',
                'value' => '081234567890',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Telepon Toko Umum',
            ],
            [
                'key' => 'whatsapp_number',
                'value' => '6281234567890',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Nomor WhatsApp Landing',
            ],
            [
                'key' => 'store_logo',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Logo Toko Umum',
            ],
            [
                'key' => 'brand_gtwash_name',
                'value' => 'GTWASH',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Brand Company GTWASH',
            ],
            [
                'key' => 'brand_gtwash_logo',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Logo Brand GTWASH',
            ],
            [
                'key' => 'brand_gtwash_slogan',
                'value' => 'Solusi Digital Cepat dan Terpercaya',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Slogan Brand GTWASH',
            ],
            [
                'key' => 'brand_mstore_name',
                'value' => 'MSTORE',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Brand Company MSTORE',
            ],
            [
                'key' => 'brand_mstore_logo',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Logo Brand MSTORE',
            ],
            [
                'key' => 'brand_mstore_slogan',
                'value' => 'Solusi Digital Cepat dan Terpercaya',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Slogan Brand MSTORE',
            ],
            [
                'key' => 'brand_mstorenet_name',
                'value' => 'MSTORE.NET',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Brand Company MSTORE.NET',
            ],
            [
                'key' => 'brand_mstorenet_logo',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Logo Brand MSTORE.NET',
            ],
            [
                'key' => 'brand_mstorenet_slogan',
                'value' => 'Solusi Digital Cepat dan Terpercaya',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Slogan Brand MSTORE.NET',
            ],
            [
                'key' => 'atk_store_name',
                'value' => 'ATK STORE',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Nama Toko ATK',
            ],
            [
                'key' => 'atk_store_address',
                'value' => 'Jl. Raya Contoh No. 123',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Alamat Toko ATK',
            ],
            [
                'key' => 'atk_store_phone',
                'value' => '0812-3456-7890',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Telepon Toko ATK',
            ],
            [
                'key' => 'atk_store_logo',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Logo Toko ATK',
            ],
            [
                'key' => 'atk_receipt_title',
                'value' => 'NOTA PENJUALAN',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Nota ATK',
            ],
            [
                'key' => 'atk_receipt_footer_title',
                'value' => '*** TERIMA KASIH ***',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Footer Nota ATK',
            ],
            [
                'key' => 'atk_receipt_footer_message',
                'value' => 'Barang yang sudah dibeli tidak dapat ditukar.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Pesan Footer Nota ATK',
            ],
            [
                'key' => 'atk_receipt_footer_note',
                'value' => '',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Catatan Tambahan Footer Nota ATK',
            ],
            [
                'key' => 'atk_receipt_powered_by',
                'value' => 'POWERED BY MSTORE',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Teks Powered Nota ATK',
            ],
            [
                'key' => 'atk_fee_bank_percent',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Fee Bank (%)',
            ],
            [
                'key' => 'atk_fee_bank_fixed',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Fee Bank (Fixed Rp)',
            ],
            [
                'key' => 'atk_fee_cashout_percent',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Fee Tarik Tunai (%)',
            ],
            [
                'key' => 'atk_fee_cashout_fixed',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Fee Tarik Tunai (Fixed Rp)',
            ],
            [
                'key' => 'atk_fee_topup_percent',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Fee Top Up (%)',
            ],
            [
                'key' => 'atk_fee_topup_fixed',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Fee Top Up (Fixed Rp)',
            ],
            [
                'key' => 'atk_fee_ppob_percent',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Fee PPOB (%)',
            ],
            [
                'key' => 'atk_fee_ppob_fixed',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Fee PPOB (Fixed Rp)',
            ],
            [
                'key' => 'wash_store_name',
                'value' => 'AUTO WASH',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Nama Toko Wash',
            ],
            [
                'key' => 'wash_store_address',
                'value' => 'Jl. Contoh Bersih No. 123',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Alamat Toko Wash',
            ],
            [
                'key' => 'wash_store_phone',
                'value' => '0812-0000-0000',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Telepon Toko Wash',
            ],
            [
                'key' => 'wash_store_logo',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Logo Toko Wash',
            ],
            [
                'key' => 'wash_receipt_title',
                'value' => 'NOTA PEMBAYARAN',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Nota Wash',
            ],
            [
                'key' => 'wash_receipt_footer_title',
                'value' => '*** TERIMA KASIH ***',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Footer Nota Wash',
            ],
            [
                'key' => 'wash_receipt_footer_message',
                'value' => 'Kepuasan Anda Kebanggaan Kami.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Pesan Footer Nota Wash',
            ],
            [
                'key' => 'wash_receipt_footer_note',
                'value' => 'Periksa kembali barang bawaan Anda sebelum meninggalkan lokasi.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Catatan Tambahan Footer Nota Wash',
            ],
            [
                'key' => 'wash_receipt_powered_by',
                'value' => 'POWERED BY MSTORE',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Teks Powered Nota Wash',
            ],
            [
                'key' => 'wash_receipt_holiday_greeting',
                'value' => 'Selamat Hari Raya  Idhul Fitri Mohon Maaf Lahir & Batin.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Ucapan Hari Raya Nota Wash',
            ],
            [
                'key' => 'duitku_merchant_code',
                'value' => '',
                'group' => 'payment',
                'type' => 'text',
                'label' => 'Duitku Merchant Code',
            ],
            [
                'key' => 'duitku_api_key',
                'value' => '',
                'group' => 'payment',
                'type' => 'text',
                'label' => 'Duitku API Key',
            ],
            [
                'key' => 'duitku_sandbox',
                'value' => '1',
                'group' => 'payment',
                'type' => 'boolean',
                'label' => 'Duitku Sandbox Mode',
            ],
            [
                'key' => 'wash_holiday_pricing_start_date',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Tanggal Mulai Harga Hari Raya Wash',
            ],
            [
                'key' => 'wash_holiday_pricing_end_date',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Tanggal Selesai Harga Hari Raya Wash',
            ],
            [
                'key' => 'telegram_wash_notification_enabled',
                'value' => '0',
                'group' => 'general',
                'type' => 'boolean',
                'label' => 'Aktifkan Notifikasi Transaksi Wash via Telegram',
            ],
            [
                'key' => 'telegram_wash_group_id',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'ID Grup Telegram Notifikasi Wash',
            ],
            [
                'key' => 'wash_loyalty_target',
                'value' => '11',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Target Cuci untuk Mendapatkan Bonus Gratis',
            ],
            [
                'key' => 'landing_internet_promo_enabled',
                'value' => '1',
                'group' => 'general',
                'type' => 'boolean',
                'label' => 'Aktifkan Promo Internet Landing',
            ],
            [
                'key' => 'landing_internet_promo_percent',
                'value' => '10',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Diskon Promo Internet Landing',
            ],
            [
                'key' => 'landing_internet_promo_label',
                'value' => 'Promo Paket Internet',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Label Promo Internet Landing',
            ],
            [
                'key' => 'pos_printer_auto_reconnect',
                'value' => '1',
                'group' => 'general',
                'type' => 'boolean',
                'label' => 'Auto Reconnect Printer',
            ],
            [
                'key' => 'pos_print_logo_enabled',
                'value' => '1',
                'group' => 'general',
                'type' => 'boolean',
                'label' => 'Cetak Logo ESC/POS',
            ],
            [
                'key' => 'pos_bluetooth_chunk_size',
                'value' => '256',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Ukuran Paket Bluetooth',
            ],
            [
                'key' => 'pos_bluetooth_chunk_delay_ms',
                'value' => '0',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Delay Antar Paket Bluetooth',
            ],
            [
                'key' => 'pos_qris_text',
                'value' => '',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Data String QRIS',
            ],
            [
                'key' => 'pos_preferred_printer_name',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Nama Printer Pilihan',
            ],
            [
                'key' => 'pos_preferred_printer_id',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'ID Printer Pilihan',
            ],
            [
                'key' => 'pos_performance_profile',
                'value' => 'ultrafast',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Profil Performa Printer',
            ],
            [
                'key' => 'pos_printer_type',
                'value' => 'escpos',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Jenis Printer',
            ],
            [
                'key' => 'pos_label_width_mm',
                'value' => '80',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Lebar Label (mm)',
            ],
            [
                'key' => 'pos_label_height_mm',
                'value' => '150',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Tinggi Label (mm)',
            ],
            [
                'key' => 'pos_label_gap_mm',
                'value' => '3',
                'group' => 'general',
                'type' => 'number',
                'label' => 'Jarak Gap Label (mm)',
            ],
            [
                'key' => 'cctv_section_badge',
                'value' => 'Security Solutions',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Badge Section CCTV',
            ],
            [
                'key' => 'cctv_section_title',
                'value' => 'Paket Instalasi CCTV',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Section CCTV',
            ],
            [
                'key' => 'cctv_package_1_speed',
                'value' => 'Basic',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Paket CCTV 1',
            ],
            [
                'key' => 'cctv_package_1_subtitle',
                'value' => '1 Kamera HD',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Subjudul Paket CCTV 1',
            ],
            [
                'key' => 'cctv_package_1_price',
                'value' => 'Rp 600Rb',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Harga Paket CCTV 1',
            ],
            [
                'key' => 'cctv_package_1_features',
                'value' => "Camera 1 Channel\nHDD 250GB\nFree Instalasi",
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Fitur Paket CCTV 1',
            ],
            [
                'key' => 'cctv_package_2_speed',
                'value' => 'Basic',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Paket CCTV 2',
            ],
            [
                'key' => 'cctv_package_2_subtitle',
                'value' => '2 Kamera HD',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Subjudul Paket CCTV 2',
            ],
            [
                'key' => 'cctv_package_2_price',
                'value' => 'Rp 1.1jt',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Harga Paket CCTV 2',
            ],
            [
                'key' => 'cctv_package_2_features',
                'value' => "Camera 2 Channel\nHDD 125GB\nFree Instalasi",
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Fitur Paket CCTV 2',
            ],
            [
                'key' => 'cctv_package_3_speed',
                'value' => 'Basic',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Paket CCTV 3',
            ],
            [
                'key' => 'cctv_package_3_subtitle',
                'value' => '2 Kamera HD',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Subjudul Paket CCTV 3',
            ],
            [
                'key' => 'cctv_package_3_price',
                'value' => 'Rp 1.9jt',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Harga Paket CCTV 3',
            ],
            [
                'key' => 'cctv_package_3_features',
                'value' => "DVR 4 Channel\nHDD 500GB\nFree Instalasi",
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Fitur Paket CCTV 3',
            ],
            [
                'key' => 'cctv_package_4_speed',
                'value' => 'Basic',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Paket CCTV 4',
            ],
            [
                'key' => 'cctv_package_4_subtitle',
                'value' => '4 Kamera HD',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Subjudul Paket CCTV 4',
            ],
            [
                'key' => 'cctv_package_4_price',
                'value' => 'Rp 1.9jt',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Harga Paket CCTV 4',
            ],
            [
                'key' => 'cctv_package_4_features',
                'value' => "DVR 4 Channel\nHDD 500GB\nFree Instalasi",
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Fitur Paket CCTV 4',
            ],
            [
                'key' => 'wedding_section_badge',
                'value' => 'Event Services',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Badge Section Wedding',
            ],
            [
                'key' => 'wedding_section_title',
                'value' => 'Layanan Wedding & Event',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Judul Section Wedding',
            ],
            [
                'key' => 'wedding_service_1_badge',
                'value' => 'Wedding',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Badge Layanan Wedding 1',
            ],
            [
                'key' => 'wedding_service_1_name',
                'value' => 'Hias Pengantin',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Nama Layanan Wedding 1',
            ],
            [
                'key' => 'wedding_service_1_desc',
                'value' => 'Dekorasi pelaminan elegan untuk akad, resepsi, dan acara keluarga.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Deskripsi Layanan Wedding 1',
            ],
            [
                'key' => 'wedding_service_1_image',
                'value' => 'storage/wash-services/SWCzU7EyNG0o3NCUZRdSxMXEPR19TqlaSxgSP26k.jpg',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Gambar Layanan Wedding 1',
            ],
            [
                'key' => 'wedding_service_2_badge',
                'value' => 'Photography',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Badge Layanan Wedding 2',
            ],
            [
                'key' => 'wedding_service_2_name',
                'value' => 'Poto Moment',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Nama Layanan Wedding 2',
            ],
            [
                'key' => 'wedding_service_2_desc',
                'value' => 'Dokumentasi foto momen spesial agar setiap detik berharga tetap terabadikan.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Deskripsi Layanan Wedding 2',
            ],
            [
                'key' => 'wedding_service_2_image',
                'value' => 'storage/wash-services/JNp0g77R9K9equSk3DaVUIvE5GZjsIMqUeb6OEVm.jpg',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Gambar Layanan Wedding 2',
            ],
            [
                'key' => 'wedding_service_3_badge',
                'value' => 'Event Support',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Badge Layanan Wedding 3',
            ],
            [
                'key' => 'wedding_service_3_name',
                'value' => 'Sewa Auning',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Nama Layanan Wedding 3',
            ],
            [
                'key' => 'wedding_service_3_desc',
                'value' => 'Penyewaan auning untuk area tamu, panggung, dan kebutuhan acara outdoor.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Deskripsi Layanan Wedding 3',
            ],
            [
                'key' => 'wedding_service_3_image',
                'value' => 'storage/wash-services/fUlfmV40jz1rCp0CC2WTtXnazm1or6ANVVJs9SI8.jpg',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Gambar Layanan Wedding 3',
            ],
        ];

        $keys = collect($defaults)->pluck('key')->toArray();
        $existingKeys = Setting::whereIn('key', $keys)->pluck('key')->toArray();
        $missing = collect($defaults)->whereNotIn('key', $existingKeys)->values()->toArray();

        if (! empty($missing)) {
            Setting::insert($missing);
            Setting::forgetCache();
        }

        \Illuminate\Support\Facades\Cache::put('settings_ensured', true, now()->addHour());
    }

    private function ensureAttendanceSettings(): void
    {
        $defaults = [
            [
                'key' => 'attendance_clock_in_start',
                'value' => '07:00',
                'group' => 'attendance',
                'type' => 'time',
                'label' => 'Jam Mulai Absen Masuk',
            ],
            [
                'key' => 'attendance_clock_in_end',
                'value' => '13:00',
                'group' => 'attendance',
                'type' => 'time',
                'label' => 'Batas Akhir Absen Masuk',
            ],
            [
                'key' => 'attendance_clock_out_start',
                'value' => '20:00',
                'group' => 'attendance',
                'type' => 'time',
                'label' => 'Jam Mulai Absen Pulang',
            ],
            [
                'key' => 'attendance_clock_out_end',
                'value' => '01:00',
                'group' => 'attendance',
                'type' => 'time',
                'label' => 'Batas Akhir Absen Pulang',
            ],
            [
                'key' => 'attendance_clock_in_early_minutes',
                'value' => '60',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Boleh Absen Masuk Lebih Awal (Menit)',
            ],
            [
                'key' => 'attendance_photo_required',
                'value' => '1',
                'group' => 'attendance',
                'type' => 'boolean',
                'label' => 'Wajib Foto Selfie',
            ],
            [
                'key' => 'attendance_photo_max_kb',
                'value' => '2048',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Maksimal Ukuran Foto Absensi (KB)',
            ],
            [
                'key' => 'attendance_photo_max_width',
                'value' => '1280',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Lebar Maksimal Foto Absensi (px)',
            ],
            [
                'key' => 'attendance_photo_compress_quality',
                'value' => '78',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Kualitas Kompresi Foto Absensi (%)',
            ],
            [
                'key' => 'attendance_office_lat',
                'value' => '',
                'group' => 'attendance',
                'type' => 'text',
                'label' => 'Office Latitude',
            ],
            [
                'key' => 'attendance_office_lng',
                'value' => '',
                'group' => 'attendance',
                'type' => 'text',
                'label' => 'Office Longitude',
            ],
            [
                'key' => 'attendance_radius',
                'value' => '100',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Attendance Radius (meters)',
            ],
            [
                'key' => 'attendance_working_days',
                'value' => '28',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Hari Kerja Per Bulan',
            ],
            [
                'key' => 'attendance_late_tolerance',
                'value' => '0',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Toleransi Terlambat (Menit)',
            ],
            // Teknisi Shift Cutoffs
            [
                'key' => 'schedule_teknisi_shift_1_cutoff',
                'value' => '13:00',
                'group' => 'schedule',
                'type' => 'time',
                'label' => 'Batas Absen Shift 1 Teknisi',
            ],
            [
                'key' => 'schedule_teknisi_shift_2_cutoff',
                'value' => '17:00',
                'group' => 'schedule',
                'type' => 'time',
                'label' => 'Batas Absen Shift 2 Teknisi',
            ],
            [
                'key' => 'schedule_teknisi_longshift_cutoff',
                'value' => '13:00',
                'group' => 'schedule',
                'type' => 'time',
                'label' => 'Batas Absen Longshift Teknisi',
            ],
            // Wash Shift Cutoffs
            [
                'key' => 'schedule_wash_shift_1_cutoff',
                'value' => '13:00',
                'group' => 'schedule',
                'type' => 'time',
                'label' => 'Batas Absen Shift 1 Wash',
            ],
            [
                'key' => 'schedule_wash_shift_2_cutoff',
                'value' => '17:00',
                'group' => 'schedule',
                'type' => 'time',
                'label' => 'Batas Absen Shift 2 Wash',
            ],
            [
                'key' => 'schedule_wash_longshift_cutoff',
                'value' => '13:00',
                'group' => 'schedule',
                'type' => 'time',
                'label' => 'Batas Absen Longshift Wash',
            ],
            [
                'key' => 'whatsapp_attendance_notification_enabled',
                'value' => '1',
                'group' => 'attendance',
                'type' => 'boolean',
                'label' => 'Aktifkan Notifikasi WhatsApp Absensi',
            ],
            [
                'key' => 'whatsapp_attendance_group_id',
                'value' => '',
                'group' => 'attendance',
                'type' => 'text',
                'label' => 'ID Grup WhatsApp Notifikasi Absensi',
            ],
            [
                'key' => 'telegram_attendance_notification_enabled',
                'value' => '1',
                'group' => 'attendance',
                'type' => 'boolean',
                'label' => 'Aktifkan Notifikasi Telegram Absensi',
            ],
            [
                'key' => 'telegram_attendance_group_id',
                'value' => '',
                'group' => 'attendance',
                'type' => 'text',
                'label' => 'ID Grup Telegram Notifikasi Absensi',
            ],
        ];

        foreach ($defaults as $setting) {
            $existing = Setting::query()->where('key', $setting['key'])->first();
            if ($existing) {
                $existing->update([
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                ]);
                continue;
            }

            Setting::create($setting);
        }
    }
}
