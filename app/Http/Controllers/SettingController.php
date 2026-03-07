<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:setting.view', only: ['index']),
            new Middleware('permission:setting.update', only: ['update']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->ensureReceiptIdentitySettings();

        $settings = Setting::where('group', '!=', 'telegram')
            ->where('key', '!=', 'subscription_packages')
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');
        $accountOptions = Account::orderBy('code')->get();

        return view('settings.index', compact('settings', 'accountOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $logoUploads = [
            'store_logo_file' => 'store_logo',
            'atk_store_logo_file' => 'atk_store_logo',
            'wash_store_logo_file' => 'wash_store_logo',
            'wedding_service_1_image_file' => 'wedding_service_1_image',
            'wedding_service_2_image_file' => 'wedding_service_2_image',
            'wedding_service_3_image_file' => 'wedding_service_3_image',
        ];
        $logoClearFlags = [
            'clear_store_logo' => 'store_logo',
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
            'atk_store_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'wash_store_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'wedding_service_1_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'wedding_service_2_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'wedding_service_3_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'clear_store_logo' => 'nullable|boolean',
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
        ]);

        foreach ($logoClearFlags as $clearKey => $settingKey) {
            if (! $request->boolean($clearKey)) {
                continue;
            }

            $oldValue = Setting::getValue($settingKey, '');
            if (is_string($oldValue) && (str_starts_with($oldValue, 'storage/settings-logos/') || str_starts_with($oldValue, 'storage/landing-wedding/'))) {
                $oldPath = str_replace('storage/', '', $oldValue);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $data[$settingKey] = '';
        }

        foreach ($logoUploads as $fileKey => $settingKey) {
            if (! $request->hasFile($fileKey)) {
                continue;
            }

            $oldValue = Setting::getValue($settingKey, '');
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
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if ($key === 'mixradius_api_token' && ($value === null || $value === '')) {
                continue;
            }
            $affected = Setting::where('key', $key)->update(['value' => $value]);
            if ($affected === 0) {
                $group = str_starts_with($key, 'mixradius_') ? 'mixradius' : 'general';
                $type = $key === 'mixradius_enforce_customer_login' ? 'boolean' : 'text';
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'group' => $group,
                    'type' => $type,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                ]);
            }
        }

        return redirect()->back()->with('success', __('Settings updated successfully.'));
    }

    private function ensureReceiptIdentitySettings(): void
    {
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
                'key' => 'store_logo',
                'value' => '',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Logo Toko Umum',
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

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                ]
            );
        }
    }
}
