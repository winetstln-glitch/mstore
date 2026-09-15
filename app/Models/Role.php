<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{






















    protected $fillable = ['name', 'label', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

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

    public static function allSystemRoleNames(): array
    {
        return array_keys((array) config('roles.definitions', []));
    }

    public static function allSystemRoleLabels(): array
    {
        return array_map(
            static fn (array $def): string => $def['label'] ?? '',
            (array) config('roles.definitions', [])
        );
    }

    public static function hiddenInUserManagement(): array
    {
        return (array) config('roles.hidden_in_user_management', ['customer']);
    }

    public static function superAdminRoleNames(): array
    {
        return (array) config('roles.super_admin_role_names', ['super-admin']);
    }
}
