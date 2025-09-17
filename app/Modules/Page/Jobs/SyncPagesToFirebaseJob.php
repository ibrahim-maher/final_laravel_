<?php
// app/Modules/Page/Jobs/SyncPagesToFirebaseJob.php

namespace App\Modules\Page\Jobs;

use App\Modules\Page\Models\Page;
use App\Modules\Page\Services\PageFirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncPagesToFirebaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;
    public $maxExceptions = 3;

    protected $batchSize;
    protected $forceSync;

    /**
     * Create a new job instance.
     */
    public function __construct($batchSize = 10, $forceSync = false)
    {
        $this->batchSize = $batchSize;
        $this->forceSync = $forceSync;
        $this->onQueue('firebase-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(PageFirebaseService $firebaseService)
    {
        try {
            Log::info('Starting Firebase sync job for pages', [
                'batch_size' => $this->batchSize,
                'force_sync' => $this->forceSync
            ]);

            // Get pages to sync
            $query = Page::active();

            if (!$this->forceSync) {
                $query->where('firebase_synced', false);
            }

            $totalPages = $query->count();

            if ($totalPages === 0) {
                Log::info('No pages to sync to Firebase');
                return;
            }

            Log::info("Found {$totalPages} pages to sync");

            $processedCount = 0;
            $successCount = 0;
            $failureCount = 0;

            // Process in batches
            $query->chunk($this->batchSize, function ($pages) use ($firebaseService, &$processedCount, &$successCount, &$failureCount) {
                foreach ($pages as $page) {
                    try {
                        $result = $firebaseService->syncPage($page);

                        if ($result) {
                            $successCount++;
                            Log::debug("Successfully synced page: {$page->id}");
                        } else {
                            $failureCount++;
                            Log::warning("Failed to sync page: {$page->id}");
                        }

                        $processedCount++;

                        // Add small delay between syncs
                        usleep(100000); // 0.1 second

                    } catch (Exception $e) {
                        $failureCount++;
                        Log::error("Exception syncing page {$page->id}: " . $e->getMessage());

                        // Mark as failed
                        $page->markSyncFailed($e->getMessage());
                    }
                }

                Log::info("Processed batch: {$processedCount}/{$totalPages} pages");
            });

            Log::info('Firebase sync job completed', [
                'total_pages' => $totalPages,
                'processed' => $processedCount,
                'successful' => $successCount,
                'failed' => $failureCount
            ]);

            // If there were failures, schedule a retry job for later
            if ($failureCount > 0 && !$this->forceSync) {
                Log::info("Scheduling retry job for {$failureCount} failed pages");
                RetryFailedPageSyncsJob::dispatch()->delay(now()->addMinutes(30));
            }
        } catch (Exception $e) {
            Log::error('Firebase sync job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception)
    {
        Log::error('Firebase sync job failed completely', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags()
    {
        return ['firebase-sync', 'pages', 'batch-' . $this->batchSize];
    }
}
