<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PruneSensitiveLogsCommand extends Command
{
    protected $signature = 'logs:prune-sensitive {--dry-run} {--only=} {--max-runtime=} {--sleep-ms=} {--max-batches=} {--max-rows=}';
    protected $description = 'Prune WhatsApp & Notification logs dengan batch aman dan monitoring';

    public function handle(ConnectionInterface $db): int
    {
        $processStartedAt = microtime(true);
        $startedAtIso = now()->toIso8601String();

        $dryRun = (bool) $this->option('dry-run');
        $only = $this->parseOnlyOption();
        $stats = [
            'whatsapp_payload_cleared' => 0,
            'whatsapp_payload_batches' => 0,
            'whatsapp_deleted' => 0,
            'whatsapp_deleted_batches' => 0,
            'notification_response_cleared' => 0,
            'notification_response_batches' => 0,
            'notification_deleted' => 0,
            'notification_deleted_batches' => 0,
        ];
        $hasError = false;

        try {
            $waPayloadDays = (int) config('log_retention.whatsapp.payload_null_after_days', 7);
            $waDeleteDays = (int) config('log_retention.whatsapp.delete_after_days', 90);
            $waBatch = max(1, (int) config('log_retention.whatsapp.batch_size', 1000));
            $waMaxBatches = $this->resolveMaxBatchesForSection((int) config('log_retention.whatsapp.max_batches', 0));
            $waMaxRows = $this->resolveMaxRowsForSection((int) config('log_retention.limits.max_rows_per_section', 0));

            $notifResponseDays = (int) config('log_retention.notification.response_null_after_days', 7);
            $notifDeleteDays = (int) config('log_retention.notification.delete_after_days', 180);
            $notifBatch = max(1, (int) config('log_retention.notification.batch_size', 1000));
            $notifMaxBatches = $this->resolveMaxBatchesForSection((int) config('log_retention.notification.max_batches', 0));
            $notifMaxRows = $this->resolveMaxRowsForSection((int) config('log_retention.limits.max_rows_per_section', 0));

            $maxRuntimeSeconds = $this->resolvePositiveIntOption('max-runtime', (int) config('log_retention.limits.max_runtime_seconds', 900));
            $sleepMs = $this->resolveNonNegativeIntOption('sleep-ms', (int) config('log_retention.limits.sleep_ms_between_batches', 25));

            if ($waPayloadDays > 0 && $only['whatsapp']) {
                try {
                    $cutoff = now()->subDays($waPayloadDays);
                    $result = $this->nullColumnInBatches($db, 'whatsapp_logs', 'payload', $cutoff, $waBatch, $waMaxBatches, $waMaxRows, $sleepMs, $dryRun, $processStartedAt, $maxRuntimeSeconds);
                    $stats['whatsapp_payload_cleared'] = $result['rows'];
                    $stats['whatsapp_payload_batches'] = $result['batches'];
                } catch (\Throwable $e) {
                    $hasError = true;
                    Log::error('Failed pruning whatsapp_logs.payload', ['error' => $e->getMessage(), 'exception' => $e]);
                }
            }

            if ($waDeleteDays > 0 && $only['whatsapp']) {
                try {
                    $cutoff = now()->subDays($waDeleteDays);
                    $result = $this->deleteInBatches($db, 'whatsapp_logs', $cutoff, $waBatch, $waMaxBatches, $waMaxRows, $sleepMs, $dryRun, $processStartedAt, $maxRuntimeSeconds);
                    $stats['whatsapp_deleted'] = $result['rows'];
                    $stats['whatsapp_deleted_batches'] = $result['batches'];
                } catch (\Throwable $e) {
                    $hasError = true;
                    Log::error('Failed deleting whatsapp_logs', ['error' => $e->getMessage(), 'exception' => $e]);
                }
            }

            if ($notifResponseDays > 0 && $only['notification']) {
                try {
                    $cutoff = now()->subDays($notifResponseDays);
                    $result = $this->nullColumnInBatches($db, 'notification_logs', 'response', $cutoff, $notifBatch, $notifMaxBatches, $notifMaxRows, $sleepMs, $dryRun, $processStartedAt, $maxRuntimeSeconds);
                    $stats['notification_response_cleared'] = $result['rows'];
                    $stats['notification_response_batches'] = $result['batches'];
                } catch (\Throwable $e) {
                    $hasError = true;
                    Log::error('Failed pruning notification_logs.response', ['error' => $e->getMessage(), 'exception' => $e]);
                }
            }

            if ($notifDeleteDays > 0 && $only['notification']) {
                try {
                    $cutoff = now()->subDays($notifDeleteDays);
                    $result = $this->deleteInBatches($db, 'notification_logs', $cutoff, $notifBatch, $notifMaxBatches, $notifMaxRows, $sleepMs, $dryRun, $processStartedAt, $maxRuntimeSeconds);
                    $stats['notification_deleted'] = $result['rows'];
                    $stats['notification_deleted_batches'] = $result['batches'];
                } catch (\Throwable $e) {
                    $hasError = true;
                    Log::error('Failed deleting notification_logs', ['error' => $e->getMessage(), 'exception' => $e]);
                }
            }
        } catch (\Throwable $e) {
            $hasError = true;
            Log::error('Retention command crashed', ['error' => $e->getMessage(), 'exception' => $e]);
        }

        $durationMs = (int) round((microtime(true) - $processStartedAt) * 1000);
        $summary = array_merge($stats, [
            'dry_run' => $dryRun,
            'duration_ms' => $durationMs,
            'started_at' => $startedAtIso,
            'finished_at' => now()->toIso8601String(),
        ]);

        Log::info('Log retention finished', $summary);

        Cache::put('mstore.log_retention.last_run', $summary, now()->addDays(8));

        $this->line(json_encode($summary, JSON_PRETTY_PRINT));

        return $hasError ? 1 : 0;
    }

    private function nullColumnInBatches(
        ConnectionInterface $db,
        string $table,
        string $column,
        Carbon $cutoff,
        int $batchSize,
        int $maxBatches,
        int $maxRows,
        int $sleepMs,
        bool $dryRun,
        float $processStartedAt,
        int $maxRuntimeSeconds
    ): array
    {
        $total = 0;
        $batches = 0;

        while (true) {
            if ($maxBatches > 0 && $batches >= $maxBatches) {
                break;
            }

            if ($maxRows > 0 && $total >= $maxRows) {
                break;
            }

            if ($maxRuntimeSeconds > 0 && (microtime(true) - $processStartedAt) >= $maxRuntimeSeconds) {
                break;
            }

            $ids = $db->table($table)
                ->select('id')
                ->whereNotNull($column)
                ->where('created_at', '<', $cutoff)
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id')
                ->all();

            if (empty($ids)) {
                break;
            }

            if (! $dryRun) {
                $db->table($table)->whereIn('id', $ids)->update([$column => null]);
            }

            $total += count($ids);
            $batches++;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        return ['rows' => $total, 'batches' => $batches];
    }

    private function deleteInBatches(
        ConnectionInterface $db,
        string $table,
        Carbon $cutoff,
        int $batchSize,
        int $maxBatches,
        int $maxRows,
        int $sleepMs,
        bool $dryRun,
        float $processStartedAt,
        int $maxRuntimeSeconds
    ): array
    {
        $total = 0;
        $batches = 0;

        while (true) {
            if ($maxBatches > 0 && $batches >= $maxBatches) {
                break;
            }

            if ($maxRows > 0 && $total >= $maxRows) {
                break;
            }

            if ($maxRuntimeSeconds > 0 && (microtime(true) - $processStartedAt) >= $maxRuntimeSeconds) {
                break;
            }

            $ids = $db->table($table)
                ->select('id')
                ->where('created_at', '<', $cutoff)
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id')
                ->all();

            if (empty($ids)) {
                break;
            }

            if (! $dryRun) {
                $db->table($table)->whereIn('id', $ids)->delete();
            }

            $total += count($ids);
            $batches++;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        return ['rows' => $total, 'batches' => $batches];
    }

    private function resolveMaxBatchesForSection(int $sectionMaxBatches): int
    {
        $cli = $this->resolveNonNegativeIntOption('max-batches', null);
        if ($cli !== null) {
            return $cli;
        }

        if ($sectionMaxBatches > 0) {
            return $sectionMaxBatches;
        }

        return max(0, (int) config('log_retention.limits.max_batches_per_section', 0));
    }

    private function resolveMaxRowsForSection(int $sectionMaxRows): int
    {
        $cli = $this->resolveNonNegativeIntOption('max-rows', null);
        if ($cli !== null) {
            return $cli;
        }

        return max(0, $sectionMaxRows);
    }

    private function parseOnlyOption(): array
    {
        $value = strtolower(trim((string) $this->option('only')));
        if ($value === '') {
            return ['whatsapp' => true, 'notification' => true];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $value))));
        $allowed = ['whatsapp' => false, 'notification' => false];
        foreach ($parts as $part) {
            if (array_key_exists($part, $allowed)) {
                $allowed[$part] = true;
            }
        }

        return $allowed['whatsapp'] || $allowed['notification']
            ? $allowed
            : ['whatsapp' => true, 'notification' => true];
    }

    private function resolvePositiveIntOption(string $name, ?int $fallback): int
    {
        $value = $this->option($name);
        if ($value === null || $value === '') {
            return max(0, (int) ($fallback ?? 0));
        }

        $int = (int) $value;
        return $int > 0 ? $int : max(0, (int) ($fallback ?? 0));
    }

    private function resolveNonNegativeIntOption(string $name, ?int $fallback): ?int
    {
        $value = $this->option($name);
        if ($value === null || $value === '') {
            return $fallback;
        }

        $int = (int) $value;
        return $int >= 0 ? $int : $fallback;
    }
}
