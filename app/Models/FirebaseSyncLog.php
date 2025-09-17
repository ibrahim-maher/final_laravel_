<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FirebaseSyncLog extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'firebase_collection',
        'firebase_document_id',
        'operation',
        'status',
        'data',
        'error_message',
        'retry_count',
        'synced_at'
    ];

    protected $casts = [
        'data' => 'array',
        'synced_at' => 'datetime',
        'retry_count' => 'integer'
    ];

    /**
     * Get the owning syncable model.
     */
    public function model(): MorphTo
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    /**
     * Scope a query to only include pending syncs.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed syncs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include successful syncs.
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include syncs with retries.
     */
    public function scopeWithRetries($query)
    {
        return $query->where('retry_count', '>', 0);
    }

    /**
     * Scope a query to only include syncs for a specific model type.
     */
    public function scopeForModel($query, $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope a query to only include syncs for a specific operation.
     */
    public function scopeForOperation($query, $operation)
    {
        return $query->where('operation', $operation);
    }

    /**
     * Check if the sync is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the sync failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the sync was successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the sync has retries.
     */
    public function hasRetries(): bool
    {
        return $this->retry_count > 0;
    }

    /**
     * Get the model class name without namespace.
     */
    public function getModelClassNameAttribute(): string
    {
        return class_basename($this->model_type);
    }

    /**
     * Get formatted error message.
     */
    public function getFormattedErrorAttribute(): string
    {
        if (!$this->error_message) {
            return 'No error';
        }

        return strlen($this->error_message) > 100 
            ? substr($this->error_message, 0, 100) . '...' 
            : $this->error_message;
    }

    /**
     * Get the duration since creation.
     */
    public function getDurationAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Static method to create a new sync log.
     */
    public static function createLog($model, $operation, $data = null): self
    {
        return self::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'firebase_collection' => method_exists($model, 'getFirebaseCollection') 
                ? $model->getFirebaseCollection() 
                : $model->getTable(),
            'firebase_document_id' => method_exists($model, 'getFirebaseDocumentId') 
                ? $model->getFirebaseDocumentId() 
                : $model->id,
            'operation' => $operation,
            'status' => 'pending',
            'data' => $data,
            'retry_count' => 0
        ]);
    }

    /**
     * Mark the sync as successful.
     */
    public function markAsSuccess($firebaseDocumentId = null): void
    {
        $this->update([
            'status' => 'success',
            'synced_at' => now(),
            'firebase_document_id' => $firebaseDocumentId ?? $this->firebase_document_id
        ]);
    }

    /**
     * Mark the sync as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1
        ]);
    }

    /**
     * Reset for retry.
     */
    public function resetForRetry(): void
    {
        $this->update([
            'status' => 'pending',
            'retry_count' => $this->retry_count + 1
        ]);
    }

    /**
     * Get statistics for sync logs.
     */
    public static function getStatistics(int $hours = 24): array
    {
        $since = now()->subHours($hours);
        
        $logs = self::where('created_at', '>=', $since)->get();

        return [
            'total' => $logs->count(),
            'success' => $logs->where('status', 'success')->count(),
            'failed' => $logs->where('status', 'failed')->count(),
            'pending' => $logs->where('status', 'pending')->count(),
            'with_retries' => $logs->where('retry_count', '>', 0)->count(),
            'by_operation' => $logs->groupBy('operation')->map->count(),
            'by_model' => $logs->groupBy('model_type')->map->count(),
            'recent_failures' => $logs->where('status', 'failed')
                                    ->sortByDesc('created_at')
                                    ->take(10)
                                    ->values()
        ];
    }

    /**
     * Clean up old sync logs.
     */
    public static function cleanup(int $daysToKeep = 30): int
    {
        return self::where('created_at', '<', now()->subDays($daysToKeep))->delete();
    }
}