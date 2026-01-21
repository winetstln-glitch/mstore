<?php

namespace Database\Seeders;

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
    }
}
