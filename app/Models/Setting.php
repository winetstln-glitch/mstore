<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected static ?array $cachedValues = null;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::forgetCache();
        });

        static::deleted(function (): void {
            self::forgetCache();
        });
    }

    /**
     * Get a setting value by key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function getValue($key, $default = null)
    {
        $values = self::allValues();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function allValues(): array
    {
        if (self::$cachedValues === null) {
            self::$cachedValues = self::query()
                ->pluck('value', 'key')
                ->all();
        }

        return self::$cachedValues;
    }

    public static function forgetCache(): void
    {
        self::$cachedValues = null;
    }
}
