<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

class ManagementRoleSidebarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed permissions and roles
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_management_role_can_see_all_sidebar_items()
    {
        // Create management user
        $user = User::factory()->create();
        $user->assignRole('management');

        // Login
        $response = $this->actingAs($user)->get('/dashboard');
        
        // Debug permissions
        $user->load('role.permissions');
        $permissions = $user->role->permissions->pluck('name')->toArray();
        dump("User Permissions Count: " . count($permissions));
        if (!in_array('ticket.view', $permissions)) dump("Missing ticket.view");
        if (!in_array('atk.view', $permissions)) dump("Missing atk.view");
        if (!in_array('atk.manage', $permissions)) dump("Missing atk.manage");
        if (!in_array('inventory.view', $permissions)) dump("Missing inventory.view");

        $response->assertStatus(200);

        // Define expected menu texts (aligned with app.blade.php and id locale)
        $menuItems = [
            'Dashboard',
            // Pelanggan & Layanan
            'Data Pelanggan',
            'Layanan Aktif',
            'Hotspot Active',
            'PPPoE Active',
            'Tiket & Gangguan',
            // 'Jadwal Pemasangan', 
            'Kalkulator PON',
            // Jaringan
            'Peta Jaringan',
            'Monitor Jaringan', // Changed from Network Monitor
            'Infrastruktur',
            'OLT',
            'ODC',
            'ODP',
            'HTB',
            // Keuangan
            'Keuangan', 
            'Dashboard Keuangan',
            'Rekap Absensi',
            'Paket Internet',
            'Data Pengurus',
            // Toko ATK
            'Toko ATK',
            'Kasir & Produk',
            'Dashboard Toko',
            'Kasir (POS)',
            'Produk & Stok',
            'Riwayat Transaksi',
            // Operasional
            'Operasional',
            'Tools & SDM',
            'Inventory / Tools',
            'Aset Saya',
            'Jadwal Teknisi',
            'Cuti / Izin',
            'Absensi Saya',
            // Sistem
            'Sistem',
            'Pengaturan',
            'Pengaturan Umum',
            'Wilayah',
            'Manajemen User',
            'Manajemen Role',
            'Whatsapp API',
            'Telegram',
            'Google Map API'
        ];

        // Debug locale
        dump("Locale: " . app()->getLocale());

        $content = $response->getContent();
        $pos = strpos($content, 'Jaringan');
        if ($pos !== false) {
             dump(substr($content, $pos, 1000));
        } else {
            dump("Jaringan header not found");
        }
        
        foreach ($menuItems as $item) {
             // Handle HTML escaping for items with special characters like '&'
             $expected = e($item);
             if (strpos($response->getContent(), $expected) === false) {
                 dump("Missing: " . $item . " (Expected: " . $expected . ")");
             }
             $response->assertSee($expected, false); // false = don't escape again, we already did
         }
    }
}
