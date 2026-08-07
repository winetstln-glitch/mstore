<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
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
                'key' => 'attendance_office_lat',
                'value' => '-6.200000', // Default Jakarta
                'group' => 'attendance',
                'type' => 'text',
                'label' => 'Latitude Kantor',
            ],
            [
                'key' => 'attendance_office_lng',
                'value' => '106.816666', // Default Jakarta
                'group' => 'attendance',
                'type' => 'text',
                'label' => 'Longitude Kantor',
            ],
            [
                'key' => 'attendance_radius',
                'value' => '100', // meters
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Radius Absensi (Meter)',
            ],
            [
                'key' => 'attendance_cooldown_minutes',
                'value' => '30',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Jeda Minimal Masuk ke Pulang (Menit)',
            ],
            [
                'key' => 'attendance_clock_in_early_minutes',
                'value' => '60',
                'group' => 'attendance',
                'type' => 'number',
                'label' => 'Boleh Absen Masuk Lebih Awal (Menit)',
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
                'key' => 'attendance_auto_alpha_enabled',
                'value' => '1',
                'group' => 'attendance',
                'type' => 'boolean',
                'label' => 'Aktifkan Pembuatan Alpha Otomatis',
            ],
            [
                'key' => 'attendance_allow_after_cutoff',
                'value' => '0',
                'group' => 'attendance',
                'type' => 'boolean',
                'label' => 'Izinkan Absen Masuk Setelah Batas Cutoff',
            ],
            // Finance Settings
            [
                'key' => 'commission_coordinator_percent',
                'value' => '15',
                'group' => 'finance',
                'type' => 'number',
                'label' => 'Persentase Komisi Koordinator (%)',
            ],
            [
                'key' => 'commission_isp_percent',
                'value' => '25',
                'group' => 'finance',
                'type' => 'number',
                'label' => 'Persentase Pembayaran ISP (%)',
            ],
            [
                'key' => 'commission_tool_percent',
                'value' => '15',
                'group' => 'finance',
                'type' => 'number',
                'label' => 'Persentase Keperluan Alat (%)',
            ],
            [
                'key' => 'store_email',
                'value' => 'support@mstore.id',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Email Toko',
            ],
            // Accounting Settings
            [
                'key' => 'accounting_retained_earnings_account_id',
                'value' => '',
                'group' => 'accounting',
                'type' => 'account',
                'label' => 'Akun Laba Berjalan (Equity)',
            ],
            // Telegram Settings
            [
                'key' => 'telegram_bot_token',
                'value' => env('TELEGRAM_BOT_TOKEN', ''),
                'group' => 'telegram',
                'type' => 'text',
                'label' => 'Telegram Bot Token',
            ],
            [
                'key' => 'telegram_technician_group_chat_id',
                'value' => env('TELEGRAM_TECHNICIAN_GROUP_CHAT_ID', ''),
                'group' => 'telegram',
                'type' => 'text',
                'label' => 'Telegram Technician Group Chat ID',
            ],
            [
                'key' => 'telegram_ticket_template',
                'value' => null,
                'group' => 'telegram',
                'type' => 'textarea',
                'label' => 'Template Notifikasi Tiket Baru',
            ],
            [
                'key' => 'telegram_ticket_solved_template',
                'value' => null,
                'group' => 'telegram',
                'type' => 'textarea',
                'label' => 'Template Notifikasi Tiket Selesai',
            ],
            // Voucher Settings
            [
                'key' => 'use_radius_for_vouchers',
                'value' => '1',
                'group' => 'voucher',
                'type' => 'boolean',
                'label' => 'Gunakan RADIUS untuk Voucher (jika tidak, voucher dibuat di semua Mikrotik via API)',
            ],
            // TR-069 (GenieACS) Settings
            [
                'key' => 'tr069_acs_url',
                'value' => 'http://192.168.150.8:7547',
                'group' => 'tr069',
                'type' => 'text',
                'label' => 'URL ACS (TR-069)',
            ],
            [
                'key' => 'tr069_acs_username',
                'value' => 'admin',
                'group' => 'tr069',
                'type' => 'text',
                'label' => 'Username ACS (TR-069)',
            ],
            [
                'key' => 'tr069_acs_password',
                'value' => 'mstore01',
                'group' => 'tr069',
                'type' => 'password',
                'label' => 'Password ACS (TR-069)',
            ],
            [
                'key' => 'tr069_connection_request_username',
                'value' => 'acs',
                'group' => 'tr069',
                'type' => 'text',
                'label' => 'Username Connection Request (TR-069)',
            ],
            [
                'key' => 'tr069_connection_request_password',
                'value' => 'acsadmin12345',
                'group' => 'tr069',
                'type' => 'password',
                'label' => 'Password Connection Request (TR-069)',
            ],
            [
                'key' => 'tr069_inform_interval',
                'value' => '200',
                'group' => 'tr069',
                'type' => 'number',
                'label' => 'Interval Inform (Detik)',
            ],
            [
                'key' => 'wash_loyalty_target',
                'value' => '11',
                'group' => 'wash',
                'type' => 'number',
                'label' => 'Target Cuci untuk Dapat Voucher Gratis',
            ],
            [
                'key' => 'wash_reward_voucher_expiry_days',
                'value' => '60',
                'group' => 'wash',
                'type' => 'number',
                'label' => 'Jumlah Hari Kadaluarsa Voucher',
            ],
            [
                'key' => 'wash_commission_car_small_medium',
                'value' => '13000',
                'group' => 'wash',
                'type' => 'number',
                'label' => 'Komisi Mobil Kecil / Sedang (Rp)',
            ],
            [
                'key' => 'wash_commission_car_large_xlarge',
                'value' => '15000',
                'group' => 'wash',
                'type' => 'number',
                'label' => 'Komisi Mobil Besar / Extra Besar (Rp)',
            ],
            [
                'key' => 'wash_commission_motor_small_medium',
                'value' => '6000',
                'group' => 'wash',
                'type' => 'number',
                'label' => 'Komisi Motor Kecil / Sedang (Rp)',
            ],
            [
                'key' => 'wash_commission_motor_large_xlarge',
                'value' => '8000',
                'group' => 'wash',
                'type' => 'number',
                'label' => 'Komisi Motor Besar / Extra Besar (Rp)',
            ],
            [
                'key' => 'wash_commission_exclude_free_wash',
                'value' => '1',
                'group' => 'wash',
                'type' => 'boolean',
                'label' => 'Tidak Berikan Komisi untuk Cuci Gratis / Voucher',
            ],
            [
                'key' => 'wash_commission_only_main_services',
                'value' => '1',
                'group' => 'wash',
                'type' => 'boolean',
                'label' => 'Hanya Hitung Komisi untuk Layanan Utama (Bukan Addon)',
            ],
            [
                'key' => 'wash_commission_require_employee',
                'value' => '0',
                'group' => 'wash',
                'type' => 'boolean',
                'label' => 'Wajib Pilih Karyawan per Item di POS',
            ],
        ];

        foreach ($settings as $setting) {
            $existing = Setting::where('key', $setting['key'])->first();

            if ($existing) {
                // Update metadata only, preserve value
                $existing->update([
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                ]);
            } else {
                Setting::create($setting);
            }
        }
        // Set default retained earnings account if empty and account 3201 exists
        $retained = Setting::where('key', 'accounting_retained_earnings_account_id')->first();
        if ($retained && empty($retained->value)) {
            $acc = Account::where('code', '3201')->first();
            if ($acc) {
                $retained->update(['value' => (string) $acc->id]);
            }
        }
    }
}
