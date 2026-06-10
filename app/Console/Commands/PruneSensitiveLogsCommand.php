<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Log;

class PruneSensitiveLogsCommand extends Command
{
    protected $signature = 'logs:prune-sensitive {--dry-run}';
    protected $description = 'Prune WhatsApp & Notification logs dengan batch aman dan monitoring';

    public function handle(ConnectionInterface $db): int
    {
        $startedAt = microtime(true);

        $dryRun = (bool) $this->option('dry-run');
        $stats = [
            'whatsapp_payload_cleared' => 0,
            'whatsapp_deleted' => 0,
            'notification_response_cleared' => 0,
            'notification_deleted' => 0,
        ];
        $hasError = false;

        try {
            $waPayloadDays = (int) config('log_retention.whatsapp.payload_null_after_days', 7);
            $waDeleteDays = (int) config('log_retention.whatsapp.delete_after_days', 90);
            $waBatch = max(1, (int) config('log_retention.whatsapp.batch_size', 1000));

            $notifResponseDays = (int) config('log_retention.notification.response_null_after_days', 7);
            $notifDeleteDays = (int) config('log_retention.notification.delete_after_days', 180);
            $notifBatch = max(1, (int) config('log_retention.notification.batch_size', 1000));

            if ($waPayloadDays > 0) {
                try {
                    $cutoff = now()->subDays($waPayloadDays);
                    $stats['whatsapp_payload_cleared'] = $this->nullColumnInBatches($db, 'whatsapp_logs', 'payload', $cutoff, $waBatch, $dryRun);
                } catch (\Throwable $e) {
                    $hasError = true;
                    Log::error('Failed pruning whatsapp_logs.payload', ['error' => $e->getMessage(), 'exception' => $e]);
                }
            }

            if ($waDeleteDays > 0) {
                try {
                    $cutoff = now()->subDays($waDeleteDays);
                    $stats['whatsapp_deleted'] = $this->deleteInBatches($db, 'whatsapp_logs', $cutoff, $waBatch, $dryRun);
                } catch (\Throwable $e) {
                    $hasError = true;
                    Log::error('Failed deleting whatsapp_logs', ['error' => $e->getMessage(), 'exception' => $e]);
                }
            }

            if ($notifResponseDays > 0) {
                try {
                    $cutoff = now()->subDays($notifResponseDays);
                    $stats['notification_response_cleared'] = $this->nullColumnInBatches($db, 'notification_logs', 'response', $cutoff, $notifBatch, $dryRun);
                } catch (\Throwable $e) {
                    $hasError = true;
                    Log::error('Failed pruning notification_logs.response', ['error' => $e->getMessage(), 'exception' => $e]);
                }
            }

            if ($notifDeleteDays > 0) {
                try {
                    $cutoff = now()->subDays($notifDeleteDays);
                    $stats['notification_deleted'] = $this->deleteInBatches($db, 'notification_logs', $cutoff, $notifBatch, $dryRun);
                } catch (\Throwable $e) {
                    $hasError = true;
                    Log::error('Failed deleting notification_logs', ['error' => $e->getMessage(), 'exception' => $e]);
                }
            }
        } catch (\Throwable $e) {
            $hasError = true;
            Log::error('Retention command crashed', ['error' => $e->getMessage(), 'exception' => $e]);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        Log::info('Log retention finished', array_merge($stats, [
            'dry_run' => $dryRun,
            'duration_ms' => $durationMs,
        ]));

        $this->line(json_encode(array_merge($stats, ['dry_run' => $dryRun, 'duration_ms' => $durationMs]), JSON_PRETTY_PRINT));

        return $hasError ? 1 : 0;
    }

    private function nullColumnInBatches(ConnectionInterface $db, string $table, string $column, Carbon $cutoff, int $batchSize, bool $dryRun): int
    {
        $total = 0;

        while (true) {
            $ids = $db->table($table)
                ->select('id')
                ->whereNotNull($column)
                ->where('created_at', '<', $cutoff)
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
        }

        return $total;
    }

    private function deleteInBatches(ConnectionInterface $db, string $table, Carbon $cutoff, int $batchSize, bool $dryRun): int
    {
        $total = 0;

        while (true) {
            $ids = $db->table($table)
                ->select('id')
                ->where('created_at', '<', $cutoff)
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
        }

        return $total;
    }
}

