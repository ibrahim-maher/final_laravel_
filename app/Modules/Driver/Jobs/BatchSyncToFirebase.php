<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FirebaseSyncService;

class BatchSyncToFirebase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $modelClass;
    protected $limit;

    /**
     * Create a new job instance.
     */
    public function __construct($modelClass, $limit = 100)
    {
        $this->modelClass = $modelClass;
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseSyncService $syncService)
    {
        $models = $this->modelClass::where('firebase_synced', false)
                                   ->limit($this->limit)
                                   ->get();

        if ($models->count() > 0) {
            $results = $syncService->batchSync($models);
            
            \Log::info('Batch sync completed', [
                'model_class' => $this->modelClass,
                'success' => $results['success'],
                'failed' => $results['failed']
            ]);

            // If there are more unsynced records, dispatch another job
            if ($this->modelClass::where('firebase_synced', false)->exists()) {
                self::dispatch($this->modelClass, $this->limit)->delay(now()->addSeconds(10));
            }
        }
    }
}