<?php
// app/Modules/Foq/Services/FoqService.php

namespace App\Modules\Foq\Services;

use App\Modules\Foq\Models\Foq;
use App\Services\FirebaseSyncService;
use App\Jobs\SyncFoqToFirebase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class FoqService
{
    protected $firebaseSyncService;
    protected $autoSyncEnabled;
    protected $syncBatchSize;
    protected $immediateSyncThreshold;

    public function __construct(FirebaseSyncService $firebaseSyncService)
    {
        $this->firebaseSyncService = $firebaseSyncService;
        $this->autoSyncEnabled = config('foq.auto_sync_firebase', true);
        $this->syncBatchSize = config('foq.sync_batch_size', 10);
        $this->immediateSyncThreshold = config('foq.immediate_sync_threshold', 5);
    }

    /**
     * Create FOQ with immediate sync option
     */
    public function createFoq(array $data, bool $syncImmediately = false)
    {
        try {
            // Set default values
            $data['view_count'] = 0;
            $data['helpful_count'] = 0;
            $data['not_helpful_count'] = 0;
            $data['status'] = $data['status'] ?? Foq::STATUS_ACTIVE;
            $data['firebase_synced'] = false;

            // Create FOQ
            $foq = Foq::create($data);

            if ($foq) {
                // Clear specific cache
                $this->clearSpecificCache($foq->id);
                
                // Sync based on preference
                if ($syncImmediately || $this->shouldSyncImmediately()) {
                    $this->syncFoqImmediately($foq, 'create');
                } else {
                    $this->queueFirebaseSync($foq, 'create');
                }

                Log::info('FOQ created successfully', [
                    'foq_id' => $foq->id,
                    'question' => $foq->question,
                    'created_by' => $data['created_by'] ?? 'system',
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
            }

            return $foq;

        } catch (Exception $e) {
            Log::error('Error creating FOQ: ' . $e->getMessage(), [
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Update FOQ with sync options
     */
    public function updateFoq(int $id, array $data, bool $syncImmediately = false)
    {
        try {
            $foq = $this->getFoqById($id);
            
            if (!$foq) {
                return false;
            }

            // Mark as unsynced for Firebase update
            $data['firebase_synced'] = false;

            $result = $foq->update($data);

            if ($result) {
                // Clear specific cache
                $this->clearSpecificCache($id);
                
                // Sync based on preference
                if ($syncImmediately || $this->shouldSyncImmediately()) {
                    $this->syncFoqImmediately($foq->fresh(), 'update');
                } else {
                    $this->queueFirebaseSync($foq->fresh(), 'update');
                }

                Log::info('FOQ updated successfully', [
                    'foq_id' => $id,
                    'updated_by' => $data['updated_by'] ?? 'system',
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error updating FOQ: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Delete FOQ with sync
     */
    public function deleteFoq(int $id)
    {
        try {
            $foq = $this->getFoqById($id);
            
            if (!$foq) {
                return false;
            }

            $result = $foq->delete();

            if ($result) {
                // Clear specific cache
                $this->clearSpecificCache($id);
                
                // Queue Firebase deletion
                $this->queueFirebaseSync($foq, 'delete');

                Log::info('FOQ deleted successfully', [
                    'foq_id' => $id,
                    'question' => $foq->question
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error deleting FOQ: ' . $e->getMessage(), [
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Immediate sync for critical operations
     */
    public function syncFoqImmediately($foq, $action)
    {
        try {
            $this->firebaseSyncService->syncModel($foq, $action);
            
            if (method_exists($foq, 'update') && $action !== 'delete') {
                $foq->update([
                    'firebase_synced' => true,
                    'firebase_synced_at' => now()
                ]);
            }
            
            Log::info('Immediate Firebase sync completed', [
                'foq_id' => $foq->id,
                'action' => $action
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Immediate Firebase sync failed', [
                'foq_id' => $foq->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to queue if immediate sync fails
            $this->queueFirebaseSync($foq, $action);
            return false;
        }
    }

    /**
     * Determine if we should sync immediately
     */
    private function shouldSyncImmediately()
    {
        if (!$this->autoSyncEnabled) {
            return false;
        }
        
        $pendingCount = Foq::where('firebase_synced', false)->count();
        
        return $pendingCount < $this->immediateSyncThreshold;
    }

    /**
     * Queue Firebase sync using Laravel Jobs
     */
    private function queueFirebaseSync($foq, $action)
    {
        try {
            SyncFoqToFirebase::dispatch($foq, $action)
                ->delay(now()->addSeconds(2))
                ->onQueue('firebase-sync');
            
            Log::info('Firebase sync job queued', [
                'foq_id' => $foq->id,
                'action' => $action
            ]);
            
        } catch (Exception $e) {
            Log::warning('Failed to queue Firebase sync job: ' . $e->getMessage());
            
            // Fallback: try immediate sync but don't block if it fails
            try {
                $this->firebaseSyncService->syncModel($foq, $action);
                if (method_exists($foq, 'update') && $action !== 'delete') {
                    $foq->update(['firebase_synced' => true]);
                }
            } catch (Exception $syncError) {
                Log::error('Immediate Firebase sync fallback failed: ' . $syncError->getMessage());
            }
        }
    }

    /**
     * Auto-sync daemon
     */
    public function runAutoSync()
    {
        try {
            if (!$this->autoSyncEnabled) {
                return [
                    'success' => true,
                    'message' => 'Auto-sync is disabled',
                    'processed' => 0
                ];
            }

            $pendingFoqs = Foq::where('firebase_synced', false)
                              ->orderBy('created_at', 'asc')
                              ->limit($this->syncBatchSize)
                              ->get();

            if ($pendingFoqs->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No pending syncs',
                    'processed' => 0
                ];
            }

            $processed = 0;
            $failed = 0;

            foreach ($pendingFoqs as $foq) {
                try {
                    $this->firebaseSyncService->syncModel($foq, 'create');
                    $foq->update([
                        'firebase_synced' => true,
                        'firebase_synced_at' => now()
                    ]);
                    $processed++;
                    
                    usleep(100000); // 0.1 second delay
                    
                } catch (Exception $e) {
                    $failed++;
                    Log::error("Auto-sync failed for FOQ {$foq->id}: " . $e->getMessage());
                }
            }

            Log::info('Auto-sync completed', [
                'processed' => $processed,
                'failed' => $failed,
                'batch_size' => $this->syncBatchSize
            ]);

            return [
                'success' => true,
                'message' => "Force sync completed. Processed: {$totalProcessed}, Failed: {$totalFailed}",
                'processed' => $totalProcessed,
                'failed' => $totalFailed
            ];

        } catch (Exception $e) {
            Log::error('Force sync all failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Force sync failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all FOQs with filters
     */
    public function getAllFoqs($filters = [])
    {
        try {
            $query = Foq::query();

            // Apply search filter
            if (!empty($filters['search'])) {
                $query->search($filters['search']);
            }

            // Apply status filter
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply category filter
            if (!empty($filters['category'])) {
                $query->byCategory($filters['category']);
            }

            // Apply type filter
            if (!empty($filters['type'])) {
                $query->byType($filters['type']);
            }

            // Apply priority filter
            if (!empty($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }

            // Apply featured filter
            if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
                $query->where('is_featured', (bool) $filters['is_featured']);
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'display_order';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            
            if ($sortBy === 'display_order') {
                $query->ordered();
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Apply limit
            $limit = min($filters['limit'] ?? 25, 100);
            
            return $query->limit($limit)->get();

        } catch (Exception $e) {
            Log::error('Error getting FOQs: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get FOQ by ID with caching
     */
    public function getFoqById(int $id)
    {
        try {
            return Cache::remember("foq_{$id}", config('foq.cache_ttl', 300), function () use ($id) {
                return Foq::find($id);
            });
        } catch (Exception $e) {
            Log::error('Error getting FOQ by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get FOQ by slug with caching
     */
    public function getFoqBySlug(string $slug)
    {
        try {
            return Cache::remember("foq_slug_{$slug}", config('foq.cache_ttl', 300), function () use ($slug) {
                return Foq::where('slug', $slug)->first();
            });
        } catch (Exception $e) {
            Log::error('Error getting FOQ by slug: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(int $id)
    {
        try {
            $foq = Foq::find($id);
            if ($foq) {
                $foq->increment('view_count');
                $this->clearSpecificCache($id);
                
                // Queue sync for view count update
                $this->queueFirebaseSync($foq->fresh(), 'update');
            }
            return true;
        } catch (Exception $e) {
            Log::error('Error incrementing view count: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Record feedback
     */
    public function recordFeedback(int $id, bool $helpful)
    {
        try {
            $foq = Foq::find($id);
            if ($foq) {
                if ($helpful) {
                    $foq->increment('helpful_count');
                } else {
                    $foq->increment('not_helpful_count');
                }
                
                $this->clearSpecificCache($id);
                
                // Queue sync for feedback update
                $this->queueFirebaseSync($foq->fresh(), 'update');
            }
            return true;
        } catch (Exception $e) {
            Log::error('Error recording feedback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk create FOQs
     */
    public function bulkCreateFoqs(array $foqsData)
    {
        try {
            $created = 0;
            $failed = 0;
            $batchSize = 25;

            for ($batch = 0; $batch < ceil(count($foqsData) / $batchSize); $batch++) {
                $batchData = array_slice($foqsData, $batch * $batchSize, $batchSize);
                
                try {
                    foreach ($batchData as $data) {
                        $data['firebase_synced'] = false;
                        $data['created_at'] = now();
                        $data['updated_at'] = now();
                    }
                    
                    DB::table('foqs')->insert($batchData);
                    $created += count($batchData);
                    
                    // Queue Firebase sync for the batch
                    $this->queueBatchFirebaseSync($batchData);
                    
                } catch (Exception $e) {
                    $failed += count($batchData);
                    Log::error('Batch creation failed: ' . $e->getMessage());
                }
            }

            $this->clearSpecificCache();

            return [
                'success' => true,
                'created' => $created,
                'failed' => $failed
            ];

        } catch (Exception $e) {
            Log::error('Error bulk creating FOQs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'created' => 0,
                'failed' => count($foqsData)
            ];
        }
    }

    /**
     * Queue batch Firebase sync
     */
    private function queueBatchFirebaseSync(array $batchData)
    {
        foreach ($batchData as $foqData) {
            try {
                if (isset($foqData['id'])) {
                    $foq = Foq::find($foqData['id']);
                    if ($foq) {
                        SyncFoqToFirebase::dispatch($foq, 'create')
                            ->delay(now()->addSeconds(rand(5, 15)))
                            ->onQueue('firebase-sync');
                    }
                }
            } catch (Exception $e) {
                Log::error("Failed to queue sync for FOQ: " . $e->getMessage());
            }
        }
    }

    /**
     * Update FOQ status
     */
    public function updateFoqStatus(int $id, string $status, string $updatedBy = null)
    {
        return $this->updateFoq($id, [
            'status' => $status,
            'updated_by' => $updatedBy ?? 'system'
        ]);
    }

    /**
     * Perform bulk actions
     */
    public function performBulkAction(string $action, array $foqIds)
    {
        try {
            $processed = 0;
            $failed = 0;

            foreach ($foqIds as $id) {
                $success = false;

                switch ($action) {
                    case 'activate':
                        $success = $this->updateFoqStatus($id, Foq::STATUS_ACTIVE);
                        break;
                    case 'deactivate':
                        $success = $this->updateFoqStatus($id, Foq::STATUS_INACTIVE);
                        break;
                    case 'draft':
                        $success = $this->updateFoqStatus($id, Foq::STATUS_DRAFT);
                        break;
                    case 'feature':
                        $success = $this->updateFoq($id, ['is_featured' => true]);
                        break;
                    case 'unfeature':
                        $success = $this->updateFoq($id, ['is_featured' => false]);
                        break;
                    case 'delete':
                        $success = $this->deleteFoq($id);
                        break;
                }

                if ($success) {
                    $processed++;
                } else {
                    $failed++;
                }
            }

            return [
                'success' => true,
                'processed_count' => $processed,
                'failed_count' => $failed
            ];

        } catch (Exception $e) {
            Log::error('Error performing bulk action: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Export FOQs
     */
    public function exportFoqs(array $filters = [])
    {
        try {
            $foqs = $this->getAllFoqs($filters);
            
            return $foqs->map(function ($foq) {
                return [
                    'id' => $foq->id,
                    'question' => $foq->question,
                    'answer' => $foq->answer,
                    'category' => $foq->category,
                    'priority' => $foq->priority,
                    'status' => $foq->status,
                    'type' => $foq->type,
                    'language' => $foq->language,
                    'view_count' => $foq->view_count,
                    'helpful_count' => $foq->helpful_count,
                    'not_helpful_count' => $foq->not_helpful_count,
                    'display_order' => $foq->display_order,
                    'is_featured' => $foq->is_featured ? 'Yes' : 'No',
                    'requires_auth' => $foq->requires_auth ? 'Yes' : 'No',
                    'helpfulness_ratio' => $foq->helpfulness_ratio,
                    'firebase_synced' => $foq->firebase_synced ? 'Yes' : 'No',
                    'published_at' => $foq->published_at?->format('Y-m-d H:i:s'),
                    'created_at' => $foq->created_at?->format('Y-m-d H:i:s')
                ];
            })->toArray();

        } catch (Exception $e) {
            Log::error('Error exporting FOQs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get FOQ statistics with caching
     */
    public function getFoqStatistics()
    {
        try {
            return Cache::remember('foq_statistics', config('foq.statistics_cache_ttl', 600), function () {
                return [
                    'total_foqs' => Foq::count(),
                    'active_foqs' => Foq::where('status', Foq::STATUS_ACTIVE)->count(),
                    'draft_foqs' => Foq::where('status', Foq::STATUS_DRAFT)->count(),
                    'inactive_foqs' => Foq::where('status', Foq::STATUS_INACTIVE)->count(),
                    'featured_foqs' => Foq::where('is_featured', true)->count(),
                    'total_views' => Foq::sum('view_count') ?? 0,
                    'total_helpful' => Foq::sum('helpful_count') ?? 0,
                    'total_not_helpful' => Foq::sum('not_helpful_count') ?? 0,
                    'average_helpfulness' => $this->calculateAverageHelpfulness(),
                    'by_category' => $this->getFoqsByCategory(),
                    'by_type' => $this->getFoqsByType(),
                    'recent_views' => Foq::where('updated_at', '>=', now()->subDays(30))->sum('view_count'),
                    'unsynced_count' => Foq::where('firebase_synced', false)->count()
                ];
            });

        } catch (Exception $e) {
            Log::error('Error getting FOQ statistics: ' . $e->getMessage());
            return [
                'total_foqs' => 0,
                'active_foqs' => 0,
                'draft_foqs' => 0,
                'inactive_foqs' => 0,
                'featured_foqs' => 0,
                'total_views' => 0,
                'total_helpful' => 0,
                'total_not_helpful' => 0,
                'average_helpfulness' => 0,
                'by_category' => [],
                'by_type' => [],
                'recent_views' => 0,
                'unsynced_count' => 0
            ];
        }
    }

    /**
     * Calculate average helpfulness across all FOQs
     */
    private function calculateAverageHelpfulness()
    {
        $foqs = Foq::select('helpful_count', 'not_helpful_count')->get();
        $totalRatio = 0;
        $count = 0;

        foreach ($foqs as $foq) {
            $total = $foq->helpful_count + $foq->not_helpful_count;
            if ($total > 0) {
                $totalRatio += ($foq->helpful_count / $total) * 100;
                $count++;
            }
        }

        return $count > 0 ? round($totalRatio / $count, 2) : 0;
    }

    /**
     * Get FOQs grouped by category
     */
    private function getFoqsByCategory()
    {
        return Foq::select('category', DB::raw('count(*) as count'))
                  ->groupBy('category')
                  ->pluck('count', 'category')
                  ->toArray();
    }

    /**
     * Get FOQs grouped by type
     */
    private function getFoqsByType()
    {
        return Foq::select('type', DB::raw('count(*) as count'))
                  ->groupBy('type')
                  ->pluck('count', 'type')
                  ->toArray();
    }

    /**
     * Optimized cache clearing
     */
    private function clearSpecificCache(int $id = null)
    {
        try {
            if ($id) {
                Cache::forget("foq_{$id}");
                $foq = Foq::find($id);
                if ($foq && $foq->slug) {
                    Cache::forget("foq_slug_{$foq->slug}");
                }
            }
            
            Cache::forget('foq_statistics');
            Cache::forget('total_foqs_count');
            
        } catch (Exception $e) {
            Log::warning('Failed to clear FOQ cache: ' . $e->getMessage());
        }
    }

    /**
     * Get total FOQs count with caching
     */
    public function getTotalFoqsCount()
    {
        try {
            return Cache::remember('total_foqs_count', config('foq.cache_ttl', 300), function () {
                return Foq::count();
            });
        } catch (Exception $e) {
            Log::error('Error getting total FOQs count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Search FOQs for public use
     */
    public function searchFoqs($query, $filters = [])
    {
        try {
            $foqQuery = Foq::active();

            if ($query) {
                $foqQuery->search($query);
            }

            if (!empty($filters['category'])) {
                $foqQuery->byCategory($filters['category']);
            }

            if (!empty($filters['type'])) {
                $foqQuery->byType($filters['type']);
            }

            return $foqQuery->ordered()
                           ->limit($filters['limit'] ?? 20)
                           ->get();

        } catch (Exception $e) {
            Log::error('Error searching FOQs: ' . $e->getMessage());
            return collect();
        }
    }
}