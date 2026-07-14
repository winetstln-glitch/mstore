<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class WhatsAppGroup extends Model
{
    protected $table = 'whatsapp_groups';

    protected $fillable = [
        'group_id',
        'group_name',
        'bot_enabled',
        'group_type',
        'metadata',
    ];

    protected $casts = [
        'bot_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public static function getOrCreate(string $groupId, ?string $groupName = null): self
    {
        return self::firstOrCreate(
            ['group_id' => $groupId],
            [
                'group_name' => $groupName ?? $groupId,
                'bot_enabled' => true,
                'group_type' => 'general',
            ]
        );
    }
}
