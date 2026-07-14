<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Installation;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Modules\Network\Events\Domain\CustomerActivated;

class InstallationObserver
{
    /**
     * Handle the Installation "created" event.
     */
    public function created(Installation $installation): void
    {
        AuditLog::log('created', $installation, [], $installation->toArray(), 'Installation created');
        Log::info("Installation {$installation->id} created for customer {$installation->customer_id}.");
        
        // Auto sync coordinator to open pasang_baru tickets
        if ($installation->coordinator_id) {
            Ticket::where('customer_id', $installation->customer_id)
                ->where('type', 'pasang_baru')
                ->where('status', 'open')
                ->update(['coordinator_id' => $installation->coordinator_id]);
        }
    }

    /**
     * Handle the Installation "updated" event.
     */
    public function updated(Installation $installation): void
    {
        AuditLog::log('updated', $installation, $installation->getOriginal(), $installation->toArray(), 'Installation updated');

        // Handle status change to completed
        if ($installation->isDirty('status') && $installation->status === 'completed') {
            $this->handleInstallationCompleted($installation);
        }

        // Sync coordinator to tickets if changed
        if ($installation->isDirty('coordinator_id')) {
            Ticket::where('customer_id', $installation->customer_id)
                ->where('type', 'pasang_baru')
                ->where('status', 'open')
                ->update(['coordinator_id' => $installation->coordinator_id]);
        }
    }

    /**
     * Handle the Installation "deleted" event.
     */
    public function deleted(Installation $installation): void
    {
        AuditLog::log('deleted', $installation, $installation->toArray(), [], 'Installation deleted');
        Log::info("Installation {$installation->id} deleted.");
    }

    /**
     * Handle the Installation "restored" event.
     */
    public function restored(Installation $installation): void
    {
        AuditLog::log('restored', $installation, [], $installation->toArray(), 'Installation restored');
    }

    /**
     * Handle the Installation "force deleted" event.
     */
    public function forceDeleted(Installation $installation): void
    {
        AuditLog::log('force_deleted', $installation, $installation->toArray(), [], 'Installation force deleted');
    }

    /**
     * Handle when installation is completed.
     */
    protected function handleInstallationCompleted(Installation $installation): void
    {
        Log::info("Installation {$installation->id} marked as completed.");
        
        // Auto activate customer if not active
        if ($installation->customer && $installation->customer->status !== 'active') {
            $installation->customer->update(['status' => 'active']);
            event(new CustomerActivated($installation->customer));
            Log::info("Customer {$installation->customer_id} auto-activated after installation completion.");
        }
        
        // Auto close related open tickets
        Ticket::where('customer_id', $installation->customer_id)
            ->where('type', 'pasang_baru')
            ->where('status', 'open')
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
        Log::info("Auto-closed pasang_baru tickets for customer {$installation->customer_id}.");
    }
}
