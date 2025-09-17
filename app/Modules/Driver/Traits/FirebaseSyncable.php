<?php
// app/Traits/FirebaseSyncable.php

namespace App\Traits;

use App\Models\FirebaseSyncLog;
use App\Jobs\SyncToFirebase;

trait FirebaseSyncable
{
    /**
     * Boot the trait
     */
    public static function bootFirebaseSyncable()
    {
        // After creating a record, sync to Firebase
        static::created(function ($model) {
            $model->syncToFirebase('create');
        });

        // After updating a record, sync to Firebase
        static::updated(function ($model) {
            $model->syncToFirebase('update');
        });

        // Before deleting a record, sync deletion to Firebase
        static::deleting(function ($model) {
            $model->syncToFirebase('delete');
        });
    }

    /**
     * Sync model to Firebase
     */
    public function syncToFirebase($operation = 'update')
    {
        // Skip if already synced (for updates)
        if ($operation === 'update' && $this->firebase_synced && 
            $this->firebase_synced_at && 
            $this->firebase_synced_at->gte($this->updated_at)) {
            return;
        }

        // Dispatch sync job
        SyncToFirebase::dispatch($this, $operation);
    }

    /**
     * Mark as synced
     */
    public function markAsSynced()
    {
        $this->update([
            'firebase_synced' => true,
            'firebase_synced_at' => now()
        ]);
    }

    /**
     * Mark as unsynced
     */
    public function markAsUnsynced()
    {
        $this->update([
            'firebase_synced' => false,
            'firebase_synced_at' => null
        ]);
    }

    /**
     * Get Firebase document ID
     */
    public function getFirebaseDocumentId()
    {
        return $this->{$this->firebaseKey ?? 'id'};
    }

    /**
     * Get Firebase collection name
     */
    public function getFirebaseCollection()
    {
        return $this->firebaseCollection ?? $this->getTable();
    }

    /**
     * Convert model to Firebase array
     */
    public function toFirebaseArray()
    {
        return $this->toArray();
    }
}