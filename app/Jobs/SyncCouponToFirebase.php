<?php
// app/Jobs/SyncCouponToFirebase.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Coupon\Models\Coupon;
use App\Services\FirebaseSyncService;
use Illuminate\Support\Facades\Log;

class SyncCouponToFirebase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $backoff = [30, 60, 120]; // Exponential backoff in seconds

    public function __construct(
        public Coupon $coupon,
        public string $action = 'create'
    ) {
        $this->onQueue('firebase-sync');
    }

    public function handle(FirebaseSyncService $firebaseSyncService): void
    {
        try {
            Log::info('Starting Firebase sync job', [
                'coupon_code' => $this->coupon->code,
                'action' => $this->action,
                'attempt' => $this->attempts()
            ]);

            $firebaseSyncService->syncModel($this->coupon, $this->action);
            
            // Mark as synced if not deleted
            if ($this->action !== 'delete' && $this->coupon->exists) {
                $this->coupon->update([
                    'firebase_synced' => true,
                    'firebase_synced_at' => now()
                ]);
            }

            Log::info('Firebase sync job completed successfully', [
                'coupon_code' => $this->coupon->code,
                'action' => $this->action
            ]);

        } catch (\Exception $e) {
            Log::error('Firebase sync job failed', [
                'coupon_code' => $this->coupon->code,
                'action' => $this->action,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Firebase sync job permanently failed', [
            'coupon_code' => $this->coupon->code,
            'action' => $this->action,
            'final_attempt' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // Optionally notify administrators
        // Could also mark the coupon with a failed sync status
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }
}