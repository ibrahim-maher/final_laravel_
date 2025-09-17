<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Log;
use App\Models\FirebaseSyncLog;

class FirebaseSyncService
{
    protected $casts = [
        'data' => 'array',
        'synced_at' => 'datetime'
    ];

    public function model()
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }
}