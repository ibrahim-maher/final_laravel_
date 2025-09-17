<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FirebaseSyncService;

class SyncToFirebase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model;
    protected $operation;

    /**
     * Create a new job instance.
     */
    public function __construct($model, $operation = 'update')
    {
        $this->model = $model;
        $this->operation = $operation;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseSyncService $syncService)
    {
        $syncService->syncModel($this->model, $this->operation);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('Firebase sync job failed', [
            'model' => get_class($this->model),
            'model_id' => $this->model->id ?? null,
            'operation' => $this->operation,
            'error' => $exception->getMessage()
        ]);
    }
}
