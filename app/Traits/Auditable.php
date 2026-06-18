<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            AuditLog::log(
                action: 'created',
                model: $model,
                newValues: $model->getDirty(),
                description: 'Created new ' . class_basename($model)
            );
        });

        static::updated(function (Model $model) {
            $oldValues = $model->getOriginal();
            $newValues = $model->getDirty();
            
            if (!empty($newValues)) {
                AuditLog::log(
                    action: 'updated',
                    model: $model,
                    oldValues: array_intersect_key($oldValues, $newValues),
                    newValues: $newValues,
                    description: 'Updated ' . class_basename($model)
                );
            }
        });

        static::deleted(function (Model $model) {
            AuditLog::log(
                action: 'deleted',
                model: $model,
                oldValues: $model->getOriginal(),
                description: 'Deleted ' . class_basename($model)
            );
        });
    }
}
