<?php

namespace Database\Seeders;

use App\Models\HotspotProfile;
use App\Models\Router;
use App\Models\WashMemberPackage;
use Illuminate\Database\Seeder;

class HotspotWifiPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $defaultRouterId = Router::where('is_active', true)->value('id');

        $vouchers = [
            [
                'name' => 'Voucher 3 Jam',
                'mikrotik_profile_name' => 'voucher-2rb',
                'package_type' => 'voucher',
                'rate_limit_mbps' => 5,
                'shared_users' => 1,
                'duration_seconds' => 3 * 3600,
                'limit_uptime' => '3h',
                'quota_mb' => null,
                'price' => 2000,
                'color_badge' => 'green',
                'description' => 'Kuota Unlimited. Kecepatan up to 5 Mbps. Masa aktif 6 jam setelah login pertama.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Voucher 6 Jam',
                'mikrotik_profile_name' => 'voucher-3rb',
                'package_type' => 'voucher',
                'rate_limit_mbps' => 5,
                'shared_users' => 1,
                'duration_seconds' => 6 * 3600,
                'limit_uptime' => '6h',
                'quota_mb' => null,
                'price' => 3000,
                'color_badge' => 'lime',
                'description' => 'Kuota Unlimited. Kecepatan up to 5 Mbps. Masa aktif 12 jam setelah login pertama.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Voucher 12 Jam',
                'mikrotik_profile_name' => 'voucher-5rb',
                'package_type' => 'voucher',
                'rate_limit_mbps' => 5,
                'shared_users' => 1,
                'duration_seconds' => 12 * 3600,
                'limit_uptime' => '12h',
                'quota_mb' => null,
                'price' => 5000,
                'color_badge' => 'blue',
                'description' => 'Kuota Unlimited. Kecepatan up to 5 Mbps. Masa aktif 1 hari setelah login pertama.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Voucher 1 Hari',
                'mikrotik_profile_name' => 'voucher-7rb',
                'package_type' => 'voucher',
                'rate_limit_mbps' => 5,
                'shared_users' => 2,
                'duration_seconds' => 24 * 3600,
                'limit_uptime' => '24h',
                'quota_mb' => null,
                'price' => 7000,
                'color_badge' => 'purple',
                'description' => 'Kuota Unlimited. Kecepatan up to 5 Mbps. Bisa dipakai 2 perangkat sekaligus.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Voucher 7 Hari',
                'mikrotik_profile_name' => 'voucher-25rb',
                'package_type' => 'voucher',
                'rate_limit_mbps' => 5,
                'shared_users' => 3,
                'duration_seconds' => 7 * 24 * 3600,
                'limit_uptime' => '7d',
                'quota_mb' => null,
                'price' => 25000,
                'color_badge' => 'orange',
                'description' => 'Kuota Unlimited 7 hari penuh. Kecepatan up to 5 Mbps. 3 perangkat bersamaan.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Paket Rumahan Silver',
                'mikrotik_profile_name' => 'home-silver',
                'package_type' => 'residential',
                'rate_limit_mbps' => 10,
                'shared_users' => 8,
                'duration_seconds' => 30 * 24 * 3600,
                'quota_mb' => null,
                'price' => 150000,
                'color_badge' => 'gray',
                'description' => 'Unlimited kuota bulanan. 10 Mbps. Cocok 3-5 pengguna.',
                'sort_order' => 10,
            ],
            [
                'name' => 'Paket Rumahan Gold',
                'mikrotik_profile_name' => 'home-gold',
                'package_type' => 'residential',
                'rate_limit_mbps' => 20,
                'shared_users' => 16,
                'duration_seconds' => 30 * 24 * 3600,
                'quota_mb' => null,
                'price' => 250000,
                'color_badge' => 'gold',
                'description' => 'Unlimited kuota bulanan. 20 Mbps. Streaming, game, WFH lancar jaya.',
                'sort_order' => 11,
            ],
            [
                'name' => 'Paket Rumahan Platinum',
                'mikrotik_profile_name' => 'home-platinum',
                'package_type' => 'residential',
                'rate_limit_mbps' => 50,
                'shared_users' => 32,
                'duration_seconds' => 30 * 24 * 3600,
                'quota_mb' => null,
                'price' => 450000,
                'color_badge' => 'purple',
                'description' => 'Unlimited 50 Mbps. Untuk kebutuhan kantor kecil / kos-kosan.',
                'sort_order' => 12,
            ],
        ];

        foreach ($vouchers as $idx => $v) {
            HotspotProfile::updateOrCreate(
                ['name' => $v['name']],
                array_merge($v, [
                    'router_id' => $defaultRouterId,
                    'is_active' => true,
                    'sort_order' => $v['sort_order'] ?? ($idx + 1),
                ])
            );
        }

        $voucher2k = HotspotProfile::where('name', 'Voucher 3 Jam')->value('id');

        $memberPackages = [
            [
                'name' => 'Paket Member Bronze WiFi',
                'code' => 'MBR-WIFI-BRONZE',
                'type' => 'wifi',
                'network_type' => 'hotspot',
                'hotspot_profile_id' => $voucher2k,
                'pppoe_profile' => 'member-wifi',
                'rate_limit_mbps' => 5,
                'daily_wifi_minutes' => 60,
                'price' => 50000,
                'duration_days' => 30,
                'discount_percent' => 0,
                'description' => 'Member WiFi 1 HP. Kuota Unlimited harian 1 jam. Speed 5 Mbps.',
                'benefits' => [
                    'Akses WiFi 60 menit / hari',
                    'Kecepatan 5 Mbps',
                    '1 perangkat aktif',
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'Paket Member 1 HP (Unlimited)',
                'code' => 'MBR-WIFI-1HP',
                'type' => 'wifi',
                'network_type' => 'pppoe',
                'hotspot_profile_id' => null,
                'pppoe_profile' => 'member-70rb',
                'rate_limit_mbps' => 5,
                'daily_wifi_minutes' => null,
                'price' => 70000,
                'duration_days' => 30,
                'discount_percent' => 0,
                'description' => '1 HP / perangkat. Kecepatan 5 Mbps unlimited 24 jam tanpa batas.',
                'benefits' => [
                    'Unlimited 24 jam',
                    'Speed 5 Mbps',
                    '1 perangkat (PPPoE / Hotspot)',
                ],
                'sort_order' => 2,
            ],
            [
                'name' => 'Paket Member 2 HP',
                'code' => 'MBR-WIFI-2HP',
                'type' => 'wifi',
                'network_type' => 'pppoe',
                'pppoe_profile' => 'member-120rb',
                'rate_limit_mbps' => 5,
                'price' => 120000,
                'duration_days' => 30,
                'description' => '2 HP / perangkat sekaligus. Unlimited 24 jam.',
                'benefits' => [
                    'Unlimited 24 jam',
                    'Speed 5 Mbps',
                    '2 perangkat',
                ],
                'sort_order' => 3,
            ],
            [
                'name' => 'Paket Member Keluarga (Cuci + WiFi)',
                'code' => 'MBR-BOTH-FAMILY',
                'type' => 'both',
                'network_type' => 'pppoe',
                'pppoe_profile' => 'member-180rb',
                'rate_limit_mbps' => 10,
                'daily_wifi_minutes' => null,
                'price' => 180000,
                'duration_days' => 30,
                'discount_percent' => 10,
                'description' => 'Paket gabungan: WiFi unlimited 10 Mbps + Diskon cuci 10% setiap kunjungan.',
                'benefits' => [
                    'Unlimited 24 jam',
                    'Speed 10 Mbps',
                    '4 perangkat',
                    'Diskon 10% semua layanan cuci',
                    'Cuci 10x GRATIS 1x',
                ],
                'sort_order' => 10,
            ],
            [
                'name' => 'Paket Member Premium (Cuci + WiFi)',
                'code' => 'MBR-BOTH-PREMIUM',
                'type' => 'both',
                'network_type' => 'pppoe',
                'pppoe_profile' => 'member-300rb',
                'rate_limit_mbps' => 20,
                'price' => 300000,
                'duration_days' => 30,
                'discount_percent' => 15,
                'description' => 'WiFi 20 Mbps + diskon cuci 15% + bonus voucher 7 hari.',
                'benefits' => [
                    'Unlimited WiFi 20 Mbps',
                    '8 perangkat',
                    'Diskon 15% cuci',
                    'Cuci 8x GRATIS 1x',
                    'Bonus Voucher 7 hari (Rp 25rb)',
                ],
                'sort_order' => 11,
            ],
        ];

        foreach ($memberPackages as $mp) {
            WashMemberPackage::updateOrCreate(
                ['code' => $mp['code']],
                array_merge($mp, [
                    'router_id' => $defaultRouterId,
                    'is_active' => true,
                ])
            );
        }
    }
}
