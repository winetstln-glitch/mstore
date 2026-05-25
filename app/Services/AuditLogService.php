<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AuditLogService
{
    public function __construct() {}

    public function getAuditLogsQuery(?int $userId = null, ?string $action = null, ?string $modelType = null): Builder
    {
        $query = AuditLog::with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($modelType) {
            $query->where('model_type', $modelType);
        }

        return $query->latest();
    }

    public function logAction(string $action, $model = null, array $oldValues = [], array $newValues = [], ?string $description = null): AuditLog
    {
        return AuditLog::log($action, $model, $oldValues, $newValues, $description);
    }

    public function getRecentLogs(int $limit = 20): Collection
    {
        return $this->getAuditLogsQuery()->limit($limit)->get();
    }
}
