<?php
// app/Modules/Page/Jobs/RetryFailedPageSyncsJob.php

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

class RetryFailedPageSyncsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;
    public $maxExceptions = 2;

    protected $maxRetryAttempts;

    /**
     * Create a new job instance.
     */
    public function __construct($maxRetryAttempts = 3)
    {
        $this->maxRetryAttempts = $maxRetryAttempts;
        $this->onQueue('firebase-retry');
    }

    /**
     * Execute the job.
     */
    public function handle(PageFirebaseService $firebaseService)
    {
        try {
            Log::info('Starting retry job for failed page syncs', [
                'max_retry_attempts' => $this->maxRetryAttempts
            ]);

            // Get pages that failed sync and haven't exceeded max retry attempts
            $failedPages = Page::where('firebase_sync_status', 'failed')
                ->where('firebase_sync_attempts', '<', $this->maxRetryAttempts)
                ->get();

            if ($failedPages->isEmpty()) {
                Log::info('No failed pages to retry');
                return;
            }

            Log::info("Found {$failedPages->count()} failed pages to retry");

            $retrySuccessCount = 0;
            $retryFailureCount = 0;

            foreach ($failedPages as $page) {
                try {
                    Log::info("Retrying sync for page: {$page->id} (attempt {$page->firebase_sync_attempts})");

                    $result = $firebaseService->syncPage($page);

                    if ($result) {
                        $retrySuccessCount++;
                        Log::info("Successfully retried sync for page: {$page->id}");
                    } else {
                        $retryFailureCount++;
                        Log::warning("Retry failed for page: {$page->id}");
                    }

                    // Add delay between retries
                    usleep(200000); // 0.2 seconds

                } catch (Exception $e) {
                    $retryFailureCount++;
                    Log::error("Exception during retry for page {$page->id}: " . $e->getMessage());

                    // The markSyncFailed method will increment attempts and update timestamp
                    $page->markSyncFailed($e->getMessage());
                }
            }

            Log::info('Retry job completed', [
                'total_retried' => $failedPages->count(),
                'successful_retries' => $retrySuccessCount,
                'failed_retries' => $retryFailureCount
            ]);

            // Check if there are still pages that haven't reached max attempts
            $remainingFailures = Page::where('firebase_sync_status', 'failed')
                ->where('firebase_sync_attempts', '<', $this->maxRetryAttempts)
                ->count();

            if ($remainingFailures > 0) {
                Log::info("Scheduling another retry job for {$remainingFailures} pages");
                // Schedule another retry in 1 hour
                self::dispatch($this->maxRetryAttempts)->delay(now()->addHour());
            }
        } catch (Exception $e) {
            Log::error('Retry job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception)
    {
        Log::error('Retry job failed completely', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags()
    {
        return ['firebase-retry', 'pages'];
    }
}
