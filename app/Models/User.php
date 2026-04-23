<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'attendance_card_code',
        'radius_username',
        'radius_type',
        'password',
        'role_id',
        'phone',
        'telegram_chat_id',
        'is_active',
        'daily_salary',
        'avatar',
        'last_seen_at',
        'last_seen_ip',
        'last_seen_user_agent',
        'attendance_device_hash',
        'attendance_device_locked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'attendance_device_locked_at' => 'datetime',
        ];
    }

    public function isOnline(int $thresholdSeconds = 90): bool
    {
        if (! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->gte(now()->subSeconds(max(10, $thresholdSeconds)));
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function coordinator(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Coordinator::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function assignedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_user');
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_user');
    }

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class, 'technician_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public static function findExistingUser(array $data): ?self
    {
        $query = self::query();

        if (! empty($data['email'])) {
            return self::where('email', $data['email'])->first();
        }

        if (! empty($data['username'])) {
            return self::where('username', $data['username'])->first();
        }

        if (! empty($data['radius_username'])) {
            return self::where('radius_username', $data['radius_username'])->first();
        }

        if (! empty($data['attendance_card_code'])) {
            return self::where('attendance_card_code', $data['attendance_card_code'])->first();
        }

        if (! empty($data['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', (string) $data['phone']);
            if (strlen($phone) >= 8) {
                // Remove leading zero if any
                $phoneTrim = ltrim($phone, '0');
                $userByPhone = self::where('phone', 'like', "%{$phoneTrim}%")->first();
                if ($userByPhone) {
                    return $userByPhone;
                }
            }
        }

        if (! empty($data['name'])) {
            return self::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($data['name']))])->first();
        }

        return null;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->role && $this->role->hasPermission($permission);
    }

    public static function generateUniqueUsername(?string $seed, ?string $fallbackEmail = null): string
    {
        $base = Str::of((string) $seed)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($base === '' && $fallbackEmail) {
            $base = Str::before(strtolower($fallbackEmail), '@');
            $base = Str::of($base)
                ->ascii()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->value();
        }

        if ($base === '') {
            $base = 'user';
        }

        $base = mb_substr($base, 0, 40);
        $candidate = $base;
        $suffix = 1;

        while (static::where('username', $candidate)->exists()) {
            $suffixText = '_'.$suffix;
            $candidate = mb_substr($base, 0, max(1, 40 - strlen($suffixText))).$suffixText;
            $suffix++;
        }

        return $candidate;
    }

    public static function generateUniqueAttendanceCardCode(?string $seed, ?int $ignoreId = null): string
    {
        $base = trim((string) $seed);
        if ($base === '') {
            $base = 'IDCARD';
        }
        $base = Str::of($base)
            ->upper()
            ->ascii()
            ->replaceMatches('/[^A-Z0-9\-]+/', '-')
            ->trim('-')
            ->value();
        if ($base === '') {
            $base = 'IDCARD';
        }
        $base = mb_substr($base, 0, 40);
        $candidate = $base;
        $suffix = 1;

        if (! static::hasAttendanceCardColumn()) {
            return $candidate;
        }

        while (static::where('attendance_card_code', $candidate)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $suffixText = '-'.$suffix;
            $candidate = mb_substr($base, 0, max(1, 40 - strlen($suffixText))).$suffixText;
            $suffix++;
        }

        return $candidate;
    }

    public static function defaultAttendanceCardCodeById(int $id): string
    {
        return 'EMP-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::created(function (self $user) {
            if (! static::hasAttendanceCardColumn()) {
                return;
            }
            if (trim((string) $user->attendance_card_code) !== '') {
                return;
            }
            $fallbackCode = static::defaultAttendanceCardCodeById((int) $user->id);
            $user->attendance_card_code = static::generateUniqueAttendanceCardCode($fallbackCode, (int) $user->id);
            $user->saveQuietly();
        });
    }

    private static function hasAttendanceCardColumn(): bool
    {
        static $hasColumn = null;
        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('users', 'attendance_card_code');
        }

        return $hasColumn;
    }

    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        $this->role()->associate($role);
        $this->save();
    }
}
