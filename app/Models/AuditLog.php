<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'before' => 'array',
        'after' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?Model $model = null, array $oldValues = [], array $newValues = [], ?string $description = null): ?self
    {
        try {
            if (! Schema::hasTable('audit_logs')) {
                return null;
            }

            $data = [
                'user_id' => auth()->id(),
                'action' => $action,
            ];

            if (Schema::hasColumn('audit_logs', 'model_type')) {
                $data['model_type'] = $model ? get_class($model) : null;
                $data['model_id'] = $model?->id;
                $data['old_values'] = $oldValues ?: null;
                $data['new_values'] = $newValues ?: null;
                $data['ip_address'] = request()->ip();
                $data['user_agent'] = request()->userAgent();
                $data['description'] = $description;
            } else {
                $data['auditable_type'] = $model ? get_class($model) : null;
                $data['auditable_id'] = $model?->id;
                $data['before'] = $oldValues ?: null;
                $data['after'] = $newValues ?: null;
            }

            return self::create($data);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }
}
