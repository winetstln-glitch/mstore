<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'admin';
    public const DIREKTUR = 'direktur';
    public const LEADER = 'leader';
    public const NOC = 'noc';
    public const NOC_LEGACY = 'network-operations-center';
    public const TECHNICIAN = 'technician';
    public const COORDINATOR = 'coordinator';
    public const FINANCE = 'finance';
    public const HRD_MANAGER = 'hrd-manager';
    public const CUSTOMER_SERVICE = 'customer-service';
    public const CUSTOMER = 'customer';
    public const RESELLER = 'reseller';
    public const KASIR_ATK = 'kasir-atk';
    public const KASIR_WASH = 'kasir-wash';
    public const KARYAWAN_WASH = 'karyawan-wash';
    public const STAFF_GUDANG = 'staff-gudang';

    protected $fillable = ['name', 'label'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasPermission($permissionName): bool
    {
        return $this->permissions->contains('name', $permissionName);
    }
}
