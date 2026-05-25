<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'label', 'group'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Group permissions by their logical tabs for UI display.
     */
    public static function getGroupedPermissions()
    {
        $allPermissions = self::orderBy('group')->get()->groupBy('group');
        
        $tabMap = [
            'Pelanggan & Layanan' => ['Customer Management', 'Ticket Management', 'Installation Management', 'Service Management'],
            'Jaringan' => [
                'ODC Management', 'ODP Management', 'HTB Management', 'OLT Management', 'Router Management',
                'Closure Management', 'PPPoE Management', 'Hotspot Management', 'Radius', 'Map', 'Network Monitor', 'Utilities'
            ],
            'Keuangan' => ['Finance', 'Investor Management', 'Accounting'],
            'Operasional' => [
                'Technician Management', 'Attendance', 'Leave Management', 'Schedule Management',
                'Inventory (Alat & Material)', 'Employee Management'
            ],
            'Toko ATK' => ['ATK Store'],
            'Cuci Kendaraan' => ['Car Wash'],
            'Sistem' => [
                'User Management', 'Role Management', 'Settings', 'Coordinator Management',
                'Region Management', 'Package Management', 'WhatsApp', 'Telegram', 'Notification', 'Integrasi'
            ],
            'Umum' => ['Dashboard', 'Profile'],
        ];

        $grouped = [];
        $tabsOrder = ['Pelanggan & Layanan', 'Jaringan', 'Keuangan', 'Operasional', 'Toko ATK', 'Cuci Kendaraan', 'Sistem', 'Umum', 'Lainnya'];

        foreach ($allPermissions as $group => $perms) {
            $foundTab = 'Lainnya';
            foreach ($tabMap as $tabName => $groups) {
                if (in_array($group, $groups)) {
                    $foundTab = $tabName;
                    break;
                }
            }
            $grouped[$foundTab][$group] = $perms;
        }

        // Sort by predefined tab order
        uksort($grouped, function($a, $b) use ($tabsOrder) {
            $posA = array_search($a, $tabsOrder);
            $posB = array_search($b, $tabsOrder);
            return ($posA === false ? 999 : $posA) <=> ($posB === false ? 999 : $posB);
        });

        return $grouped;
    }
}
