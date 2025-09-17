<?php
// app/Jobs/SyncFoqToFirebase.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncFoqToFirebase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $foqData;
    protected $action;

    public function __construct($foqData, $action = 'update')
    {
        $this->foqData = $foqData;
        $this->action = $action;
    }

    public function handle()
    {
        try {
            // Since you're using a simplified model without Firebase sync,
            // we'll just log the action for now
            Log::info('Firebase sync job executed', [
                'action' => $this->action,
                'foq_id' => $this->foqData['id'] ?? 'unknown',
                'question' => $this->foqData['question'] ?? 'N/A'
            ]);

            // If you want to implement actual Firebase sync later,
            // you would add Firebase SDK calls here
            
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase sync job failed', [
                'error' => $e->getMessage(),
                'foq_data' => $this->foqData
            ]);
            
            throw $e;
        }
    }
}