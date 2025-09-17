<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\FirebaseSyncLog;
use App\Services\FirestoreService;
use Exception;

class FirebaseSyncService
{
    protected $firestoreService;

    public function __construct(FirestoreService $firestoreService)
    {
        $this->firestoreService = $firestoreService;
    }

    /**
     * Sync a single model to Firebase
     */
    public function syncModel($model, $operation = 'update')
    {
        try {
            $collection = $model->getFirebaseCollection();
            $documentId = $model->getFirebaseDocumentId();
            $data = $model->toFirebaseArray();

            Log::info('Starting Firebase sync', [
                'operation' => $operation,
                'collection' => $collection,
                'document_id' => $documentId,
                'model_class' => get_class($model)
            ]);

            $success = false;

            switch ($operation) {
                case 'create':
                    $result = $this->firestoreService->collection($collection)->create($data, $documentId);
                    $success = $result !== null;
                    break;
                    
                case 'update':
                    // Check if document exists first
                    if ($this->firestoreService->collection($collection)->exists($documentId)) {
                        $success = $this->firestoreService->collection($collection)->update($documentId, $data);
                    } else {
                        // Create if doesn't exist
                        $result = $this->firestoreService->collection($collection)->create($data, $documentId);
                        $success = $result !== null;
                    }
                    break;
                    
                case 'delete':
                    $success = $this->firestoreService->collection($collection)->delete($documentId);
                    break;
                    
                default:
                    throw new Exception("Unknown operation: {$operation}");
            }

            if ($success) {
                // Mark model as synced
                if (method_exists($model, 'markAsSynced')) {
                    $model->markAsSynced();
                }

                // Log successful sync
                $this->logSync($model, $operation, 'success');

                Log::info('Firebase sync completed successfully', [
                    'operation' => $operation,
                    'collection' => $collection,
                    'document_id' => $documentId
                ]);

                return true;
            } else {
                throw new Exception('Firestore operation failed');
            }

        } catch (Exception $e) {
            Log::error('Firebase sync failed', [
                'operation' => $operation,
                'model_class' => get_class($model),
                'model_id' => $model->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log failed sync
            $this->logSync($model, $operation, 'failed', $e->getMessage());

            return false;
        }
    }

    /**
     * Batch sync multiple models
     */
    public function batchSync($models)
    {
        $success = 0;
        $failed = 0;
        $operations = [];

        // Group models by collection for better batch performance
        $modelsByCollection = [];
        foreach ($models as $model) {
            $collection = $model->getFirebaseCollection();
            if (!isset($modelsByCollection[$collection])) {
                $modelsByCollection[$collection] = [];
            }
            $modelsByCollection[$collection][] = $model;
        }

        // Process each collection separately
        foreach ($modelsByCollection as $collection => $collectionModels) {
            try {
                // Prepare batch operations for this collection
                $batchOps = [];
                foreach ($collectionModels as $model) {
                    $batchOps[] = [
                        'type' => 'update',
                        'id' => $model->getFirebaseDocumentId(),
                        'data' => $model->toFirebaseArray()
                    ];
                }

                // Execute batch operation
                $result = $this->firestoreService->collection($collection)->batch($batchOps);
                
                if (!empty($result)) {
                    foreach ($collectionModels as $model) {
                        if (method_exists($model, 'markAsSynced')) {
                            $model->markAsSynced();
                        }
                        $this->logSync($model, 'batch_update', 'success');
                        $success++;
                    }
                } else {
                    foreach ($collectionModels as $model) {
                        $this->logSync($model, 'batch_update', 'failed', 'Batch operation failed');
                        $failed++;
                    }
                }

            } catch (Exception $e) {
                Log::error('Batch sync failed for collection: ' . $collection, [
                    'error' => $e->getMessage(),
                    'models_count' => count($collectionModels)
                ]);

                foreach ($collectionModels as $model) {
                    $this->logSync($model, 'batch_update', 'failed', $e->getMessage());
                    $failed++;
                }
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'total' => count($models)
        ];
    }

    /**
     * Sync all models of a given class
     */
    public function syncAllModels($modelClass, $batchSize = 50)
    {
        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        try {
            $query = $modelClass::query();
            
            // Add condition to sync only unsynced models if the trait is used
            if (method_exists($modelClass, 'scopeUnsynced')) {
                $query->where('firebase_synced', false);
            }

            $query->chunk($batchSize, function ($models) use (&$totalProcessed, &$totalSuccess, &$totalFailed) {
                $result = $this->batchSync($models);
                $totalProcessed += $result['total'];
                $totalSuccess += $result['success'];
                $totalFailed += $result['failed'];

                Log::info('Synced batch of models', [
                    'processed' => $result['total'],
                    'success' => $result['success'],
                    'failed' => $result['failed']
                ]);
            });

        } catch (Exception $e) {
            Log::error('Error in syncAllModels: ' . $e->getMessage());
        }

        return [
            'processed' => $totalProcessed,
            'success' => $totalSuccess,
            'failed' => $totalFailed
        ];
    }

    /**
     * Get document from Firebase
     */
    public function getDocument($collection, $documentId)
    {
        try {
            return $this->firestoreService->collection($collection)->find($documentId);
        } catch (Exception $e) {
            Log::error('Failed to get Firebase document', [
                'collection' => $collection,
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if document exists in Firebase
     */
    public function documentExists($collection, $documentId)
    {
        try {
            return $this->firestoreService->collection($collection)->exists($documentId);
        } catch (Exception $e) {
            Log::error('Failed to check Firebase document existence', [
                'collection' => $collection,
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Query collection
     */
    public function queryCollection($collection, $conditions = [])
    {
        try {
            $query = $this->firestoreService->collection($collection);
            
            // Apply conditions using your FirestoreService where method
            foreach ($conditions as $condition) {
                if (count($condition) === 3) {
                    $query = $query->where($condition[0], $condition[1], $condition[2]);
                }
            }
            
            return $query->get();
        } catch (Exception $e) {
            Log::error('Failed to query Firebase collection', [
                'collection' => $collection,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Test Firebase connection using existing FirestoreService
     */
    public function testConnection()
    {
        try {
            return $this->firestoreService->healthCheck();
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Firebase connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get collection statistics
     */
    public function getCollectionStats($collection)
    {
        try {
            return $this->firestoreService->collection($collection)->getStats();
        } catch (Exception $e) {
            Log::error('Failed to get collection stats', [
                'collection' => $collection,
                'error' => $e->getMessage()
            ]);
            return [
                'collection' => $collection,
                'document_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear cache for a collection
     */
    public function clearCollectionCache($collection)
    {
        try {
            $this->firestoreService->collection($collection)->clearCache();
            Log::info("Cleared cache for collection: {$collection}");
            return true;
        } catch (Exception $e) {
            Log::error('Failed to clear collection cache', [
                'collection' => $collection,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync a model from Firebase to local database
     */
    public function syncFromFirebase($modelClass, $documentId)
    {
        try {
            $model = new $modelClass();
            $collection = $model->getFirebaseCollection();
            
            $firebaseData = $this->getDocument($collection, $documentId);
            
            if (!$firebaseData) {
                Log::warning('Document not found in Firebase', [
                    'collection' => $collection,
                    'document_id' => $documentId
                ]);
                return null;
            }

            // Find or create local model
            $localModel = $modelClass::where($model->getKeyName(), $documentId)->first();
            
            if (!$localModel) {
                $localModel = new $modelClass();
                $localModel->{$model->getKeyName()} = $documentId;
            }

            // Update local model with Firebase data
            foreach ($firebaseData as $key => $value) {
                if ($key !== 'id') { // Skip the ID field
                    $localModel->$key = $value;
                }
            }

            $localModel->firebase_synced = true;
            $localModel->firebase_synced_at = now();
            $localModel->save();

            Log::info('Successfully synced from Firebase to local', [
                'model_class' => $modelClass,
                'document_id' => $documentId
            ]);

            return $localModel;

        } catch (Exception $e) {
            Log::error('Failed to sync from Firebase', [
                'model_class' => $modelClass,
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Log sync operation
     */
    protected function logSync($model, $operation, $status, $errorMessage = null)
    {
        try {
            // Only log if FirebaseSyncLog model exists
            if (class_exists('App\Models\FirebaseSyncLog')) {
                FirebaseSyncLog::create([
                    'model_type' => get_class($model),
                    'model_id' => $model->id ?? null,
                    'operation' => $operation,
                    'status' => $status,
                    'error_message' => $errorMessage,
                    'data' => $model->toArray(),
                    'synced_at' => now()
                ]);
            }
        } catch (Exception $e) {
            // Don't fail the sync if logging fails
            Log::warning('Failed to log Firebase sync', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats()
    {
        if (!class_exists('App\Models\FirebaseSyncLog')) {
            return [
                'total_syncs' => 0,
                'successful_syncs' => 0,
                'failed_syncs' => 0,
                'last_sync' => null
            ];
        }

        try {
            $totalSyncs = FirebaseSyncLog::count();
            $successfulSyncs = FirebaseSyncLog::where('status', 'success')->count();
            $failedSyncs = FirebaseSyncLog::where('status', 'failed')->count();
            $lastSync = FirebaseSyncLog::latest('synced_at')->first();

            return [
                'total_syncs' => $totalSyncs,
                'successful_syncs' => $successfulSyncs,
                'failed_syncs' => $failedSyncs,
                'success_rate' => $totalSyncs > 0 ? round(($successfulSyncs / $totalSyncs) * 100, 2) : 0,
                'last_sync' => $lastSync ? $lastSync->synced_at : null,
                'recent_failures' => FirebaseSyncLog::where('status', 'failed')
                    ->where('synced_at', '>=', now()->subHours(24))
                    ->count()
            ];
        } catch (Exception $e) {
            Log::error('Failed to get sync stats: ' . $e->getMessage());
            return [
                'total_syncs' => 0,
                'successful_syncs' => 0,
                'failed_syncs' => 0,
                'last_sync' => null
            ];
        }
    }

    /**
     * Get recent sync failures for debugging
     */
    public function getRecentFailures($limit = 10)
    {
        if (!class_exists('App\Models\FirebaseSyncLog')) {
            return [];
        }

        try {
            return FirebaseSyncLog::where('status', 'failed')
                ->latest('synced_at')
                ->limit($limit)
                ->get()
                ->map(function ($log) {
                    return [
                        'model_type' => $log->model_type,
                        'model_id' => $log->model_id,
                        'operation' => $log->operation,
                        'error_message' => $log->error_message,
                        'synced_at' => $log->synced_at
                    ];
                });
        } catch (Exception $e) {
            Log::error('Failed to get recent failures: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retry failed syncs
     */
    public function retryFailedSyncs($limit = 50)
    {
        if (!class_exists('App\Models\FirebaseSyncLog')) {
            return ['retried' => 0, 'success' => 0, 'failed' => 0];
        }

        try {
            $failedSyncs = FirebaseSyncLog::where('status', 'failed')
                ->where('synced_at', '>=', now()->subHours(24)) // Only retry recent failures
                ->limit($limit)
                ->get();

            $retried = 0;
            $success = 0;
            $failed = 0;

            foreach ($failedSyncs as $failedSync) {
                try {
                    $modelClass = $failedSync->model_type;
                    $model = $modelClass::find($failedSync->model_id);

                    if ($model) {
                        $retried++;
                        if ($this->syncModel($model, $failedSync->operation)) {
                            $success++;
                        } else {
                            $failed++;
                        }
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to retry sync', [
                        'sync_log_id' => $failedSync->id,
                        'error' => $e->getMessage()
                    ]);
                    $failed++;
                }
            }

            return [
                'retried' => $retried,
                'success' => $success,
                'failed' => $failed
            ];

        } catch (Exception $e) {
            Log::error('Failed to retry failed syncs: ' . $e->getMessage());
            return ['retried' => 0, 'success' => 0, 'failed' => 0];
        }
    }
}