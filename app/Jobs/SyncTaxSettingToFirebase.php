<?php
// app/Jobs/SyncTaxSettingToFirebase.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\TaxSetting\Models\TaxSetting;
use App\Services\FirebaseSyncService;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncTaxSettingToFirebase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taxSetting;
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
    public function __construct(TaxSetting $taxSetting, string $action = 'create')
    {
        $this->taxSetting = $taxSetting;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseSyncService $firebaseSyncService)
    {
        try {
            Log::info('Starting Firebase sync for tax setting', [
                'tax_setting_id' => $this->taxSetting->id,
                'action' => $this->action,
                'attempt' => $this->attempts()
            ]);

            // Perform the sync
            $firebaseSyncService->syncModel($this->taxSetting, $this->action);

            // Mark as synced if not deleted
            if ($this->action !== 'delete' && $this->taxSetting->exists) {
                $this->taxSetting->markAsSynced();
            }

            Log::info('Firebase sync completed for tax setting', [
                'tax_setting_id' => $this->taxSetting->id,
                'action' => $this->action
            ]);

        } catch (Exception $e) {
            Log::error('Firebase sync failed for tax setting', [
                'tax_setting_id' => $this->taxSetting->id,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Mark sync as failed if exists
            if ($this->taxSetting->exists) {
                $this->taxSetting->markSyncFailed($e->getMessage());
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
        Log::error('Firebase sync job permanently failed for tax setting', [
            'tax_setting_id' => $this->taxSetting->id,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark as permanently failed if exists
        if ($this->taxSetting->exists) {
            $this->taxSetting->markSyncFailed("Job failed after {$this->tries} attempts: " . $exception->getMessage());
        }
    }
}

