<?php
// app/Jobs/SyncCommissionToFirebase.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Commission\Models\Commission;
use App\Services\FirebaseSyncService;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncCommissionToFirebase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $commission;
    protected $action;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(Commission $commission, string $action = 'create')
    {
        $this->commission = $commission;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseSyncService $firebaseSyncService)
    {
        try {
            Log::info('Starting Firebase sync for commission', [
                'commission_id' => $this->commission->id,
                'action' => $this->action,
                'attempt' => $this->attempts()
            ]);

            // Perform the sync
            $firebaseSyncService->syncModel($this->commission, $this->action);

            // Mark as synced if not deleted
            if ($this->action !== 'delete' && $this->commission->exists) {
                $this->commission->markAsSynced();
            }

            Log::info('Firebase sync completed for commission', [
                'commission_id' => $this->commission->id,
                'action' => $this->action
            ]);

        } catch (Exception $e) {
            Log::error('Firebase sync failed for commission', [
                'commission_id' => $this->commission->id,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Mark sync as failed if exists
            if ($this->commission->exists) {
                $this->commission->markSyncFailed($e->getMessage());
            }

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception)
    {
        Log::error('Firebase sync job permanently failed for commission', [
            'commission_id' => $this->commission->id,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark as permanently failed if exists
        if ($this->commission->exists) {
            $this->commission->markSyncFailed("Job failed after {$this->tries} attempts: " . $exception->getMessage());
        }
    }
}