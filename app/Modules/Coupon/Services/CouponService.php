<?php
// app/Modules/Coupon/Services/CouponService.php

namespace App\Modules\Coupon\Services;

use App\Modules\Coupon\Models\Coupon;
use App\Modules\Coupon\Models\CouponUsage;
use App\Services\FirebaseSyncService;
use App\Jobs\SyncCouponToFirebase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class CouponService
{
    protected $firebaseSyncService;
    protected $autoSyncEnabled;
    protected $syncBatchSize;
    protected $immediateSyncThreshold;

    public function __construct(FirebaseSyncService $firebaseSyncService)
    {
        $this->firebaseSyncService = $firebaseSyncService;
        $this->autoSyncEnabled = config('coupon.auto_sync_firebase', true);
        $this->syncBatchSize = config('coupon.sync_batch_size', 10);
        $this->immediateSyncThreshold = config('coupon.immediate_sync_threshold', 5);
    }

    /**
     * Enhanced create coupon with immediate sync option
     */
    public function createCoupon(array $data, bool $syncImmediately = false)
    {
        try {
            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateCouponCode();
            }

            // Validate code uniqueness
            if (Coupon::where('code', $data['code'])->exists()) {
                throw new Exception('Coupon code already exists');
            }

            // Set default values
            $data['used_count'] = 0;
            $data['status'] = $data['status'] ?? Coupon::STATUS_ENABLED;
            $data['firebase_synced'] = false;

            // Create coupon
            $coupon = Coupon::create($data);

            if ($coupon) {
                // Clear specific cache
                $this->clearSpecificCache($coupon->code);
                
                // Sync based on preference
                if ($syncImmediately || $this->shouldSyncImmediately()) {
                    $this->syncCouponImmediately($coupon, 'create');
                } else {
                    $this->queueFirebaseSync($coupon, 'create');
                }

                Log::info('Coupon created successfully', [
                    'coupon_code' => $coupon->code,
                    'created_by' => $data['created_by'] ?? 'system',
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
            }

            return $coupon;

        } catch (Exception $e) {
            Log::error('Error creating coupon: ' . $e->getMessage(), [
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Enhanced update coupon with sync options
     */
    public function updateCoupon(string $code, array $data, bool $syncImmediately = false)
    {
        try {
            $coupon = $this->getCouponByCode($code);
            
            if (!$coupon) {
                return false;
            }

            // Don't allow changing the code
            unset($data['code']);
            
            // Mark as unsynced for Firebase update
            $data['firebase_synced'] = false;

            $result = $coupon->update($data);

            if ($result) {
                // Clear specific cache
                $this->clearSpecificCache($code);
                
                // Sync based on preference
                if ($syncImmediately || $this->shouldSyncImmediately()) {
                    $this->syncCouponImmediately($coupon->fresh(), 'update');
                } else {
                    $this->queueFirebaseSync($coupon->fresh(), 'update');
                }

                Log::info('Coupon updated successfully', [
                    'coupon_code' => $code,
                    'updated_by' => $data['updated_by'] ?? 'system',
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error updating coupon: ' . $e->getMessage(), [
                'code' => $code,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Delete coupon with sync
     */
    public function deleteCoupon(string $code)
    {
        try {
            $coupon = $this->getCouponByCode($code);
            
            if (!$coupon) {
                return false;
            }

            // Check if coupon has been used
            if ($coupon->used_count > 0) {
                // Instead of deleting, disable the coupon
                return $this->updateCoupon($code, [
                    'status' => Coupon::STATUS_DISABLED,
                    'updated_by' => auth()->id() ?? 'system'
                ]);
            }

            $result = $coupon->delete();

            if ($result) {
                // Clear specific cache
                $this->clearSpecificCache($code);
                
                // Queue Firebase deletion
                $this->queueFirebaseSync($coupon, 'delete');

                Log::info('Coupon deleted successfully', [
                    'coupon_code' => $code
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error deleting coupon: ' . $e->getMessage(), [
                'code' => $code
            ]);
            return false;
        }
    }

    /**
     * Immediate sync for critical operations
     */
    private function syncCouponImmediately($coupon, $action)
    {
        try {
            $this->firebaseSyncService->syncModel($coupon, $action);
            
            if (method_exists($coupon, 'update') && $action !== 'delete') {
                $coupon->update([
                    'firebase_synced' => true,
                    'firebase_synced_at' => now()
                ]);
            }
            
            Log::info('Immediate Firebase sync completed', [
                'coupon_code' => $coupon->code,
                'action' => $action
            ]);
            
        } catch (Exception $e) {
            Log::error('Immediate Firebase sync failed', [
                'coupon_code' => $coupon->code,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to queue if immediate sync fails
            $this->queueFirebaseSync($coupon, $action);
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
        
        $pendingCount = Coupon::where('firebase_synced', false)->count();
        
        return $pendingCount < $this->immediateSyncThreshold;
    }

    /**
     * Queue Firebase sync using Laravel Jobs
     */
    private function queueFirebaseSync($coupon, $action)
    {
        try {
            SyncCouponToFirebase::dispatch($coupon, $action)
                ->delay(now()->addSeconds(2))
                ->onQueue('firebase-sync');
            
            Log::info('Firebase sync job queued', [
                'coupon_code' => $coupon->code,
                'action' => $action
            ]);
            
        } catch (Exception $e) {
            Log::warning('Failed to queue Firebase sync job: ' . $e->getMessage());
            
            // Fallback: try immediate sync but don't block if it fails
            try {
                $this->firebaseSyncService->syncModel($coupon, $action);
                if (method_exists($coupon, 'update') && $action !== 'delete') {
                    $coupon->update(['firebase_synced' => true]);
                }
            } catch (Exception $syncError) {
                Log::error('Immediate Firebase sync fallback failed: ' . $syncError->getMessage());
            }
        }
    }

    /**
     * Auto-sync daemon - run this via scheduled task
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

            $pendingCoupons = Coupon::where('firebase_synced', false)
                                  ->orderBy('created_at', 'asc')
                                  ->limit($this->syncBatchSize)
                                  ->get();

            if ($pendingCoupons->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No pending syncs',
                    'processed' => 0
                ];
            }

            $processed = 0;
            $failed = 0;

            foreach ($pendingCoupons as $coupon) {
                try {
                    $this->firebaseSyncService->syncModel($coupon, 'create');
                    $coupon->update([
                        'firebase_synced' => true,
                        'firebase_synced_at' => now()
                    ]);
                    $processed++;
                    
                    // Small delay to prevent overwhelming Firebase
                    usleep(100000); // 0.1 second
                    
                } catch (Exception $e) {
                    $failed++;
                    Log::error("Auto-sync failed for coupon {$coupon->code}: " . $e->getMessage());
                }
            }

            Log::info('Auto-sync completed', [
                'processed' => $processed,
                'failed' => $failed,
                'batch_size' => $this->syncBatchSize
            ]);

            return [
                'success' => true,
                'message' => "Auto-sync processed {$processed} coupons",
                'processed' => $processed,
                'failed' => $failed
            ];

        } catch (Exception $e) {
            Log::error('Auto-sync daemon error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Auto-sync failed: ' . $e->getMessage(),
                'processed' => 0
            ];
        }
    }

    /**
     * Check sync health and retry failed syncs
     */
    public function checkSyncHealth()
    {
        try {
            $stats = [
                'total_coupons' => Coupon::count(),
                'synced_coupons' => Coupon::where('firebase_synced', true)->count(),
                'pending_syncs' => Coupon::where('firebase_synced', false)->count(),
                'old_pending' => Coupon::where('firebase_synced', false)
                                     ->where('created_at', '<', now()->subHours(1))
                                     ->count()
            ];

            // If there are old pending syncs, trigger a cleanup
            if ($stats['old_pending'] > 0) {
                Log::warning('Found old pending syncs, triggering cleanup', $stats);
                $this->runAutoSync();
            }

            return $stats;

        } catch (Exception $e) {
            Log::error('Sync health check failed: ' . $e->getMessage());
            return [
                'total_coupons' => 0,
                'synced_coupons' => 0,
                'pending_syncs' => 0,
                'old_pending' => 0
            ];
        }
    }

    /**
     * Force sync all unsynced coupons
     */
    public function forceSyncAll()
    {
        try {
            $totalUnsynced = Coupon::where('firebase_synced', false)->count();
            
            if ($totalUnsynced === 0) {
                return [
                    'success' => true,
                    'message' => 'All coupons are already synced',
                    'processed' => 0
                ];
            }

            // Process in batches to prevent memory issues
            $batchSize = 20;
            $totalProcessed = 0;
            $totalFailed = 0;

            do {
                $batch = Coupon::where('firebase_synced', false)
                              ->limit($batchSize)
                              ->get();

                foreach ($batch as $coupon) {
                    try {
                        $this->firebaseSyncService->syncModel($coupon, 'create');
                        $coupon->update([
                            'firebase_synced' => true,
                            'firebase_synced_at' => now()
                        ]);
                        $totalProcessed++;
                    } catch (Exception $e) {
                        $totalFailed++;
                        Log::error("Force sync failed for {$coupon->code}: " . $e->getMessage());
                    }
                    
                    // Small delay
                    usleep(50000); // 0.05 second
                }

            } while ($batch->count() === $batchSize);

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
     * Get all coupons with filters
     */
    public function getAllCoupons($filters = [])
    {
        try {
            $query = Coupon::query();

            // Apply search filter
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply coupon type filter
            if (!empty($filters['coupon_type'])) {
                $query->where('coupon_type', $filters['coupon_type']);
            }

            // Apply discount type filter
            if (!empty($filters['discount_type'])) {
                $query->where('discount_type', $filters['discount_type']);
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortDirection = $filters['sort_direction'] ?? 'desc';
            $query->orderBy($sortBy, $sortDirection);

            // Apply limit
            $limit = min($filters['limit'] ?? 25, 100);
            
            return $query->limit($limit)->get();

        } catch (Exception $e) {
            Log::error('Error getting coupons: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get coupon by code with caching
     */
    public function getCouponByCode(string $code)
    {
        try {
            return Cache::remember("coupon_{$code}", config('coupon.cache_ttl', 300), function () use ($code) {
                return Coupon::where('code', $code)->first();
            });
        } catch (Exception $e) {
            Log::error('Error getting coupon by code: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Bulk create coupons with batch processing
     */
    public function bulkCreateCoupons(array $baseData, int $count, $codePrefix = '')
    {
        try {
            $created = 0;
            $failed = 0;
            $batchSize = 25;

            // Process in batches to prevent memory and timeout issues
            for ($batch = 0; $batch < ceil($count / $batchSize); $batch++) {
                $batchCoupons = [];
                $currentBatchSize = min($batchSize, $count - ($batch * $batchSize));

                // Prepare batch data
                for ($i = 0; $i < $currentBatchSize; $i++) {
                    $data = $baseData;
                    $data['code'] = $this->generateCouponCode($codePrefix);
                    $data['used_count'] = 0;
                    $data['firebase_synced'] = false;
                    $data['created_at'] = now();
                    $data['updated_at'] = now();
                    
                    $batchCoupons[] = $data;
                }

                try {
                    // Batch insert for better performance
                    DB::table('coupons')->insert($batchCoupons);
                    $created += $currentBatchSize;
                    
                    // Queue Firebase sync for the batch
                    $this->queueBatchFirebaseSync($batchCoupons);
                    
                } catch (Exception $e) {
                    $failed += $currentBatchSize;
                    Log::error('Batch creation failed: ' . $e->getMessage());
                }
            }

            // Clear cache after bulk operation
            $this->clearSpecificCache();

            return [
                'success' => true,
                'created' => $created,
                'failed' => $failed
            ];

        } catch (Exception $e) {
            Log::error('Error bulk creating coupons: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'created' => 0,
                'failed' => $count
            ];
        }
    }

    /**
     * Queue batch Firebase sync using Jobs
     */
    private function queueBatchFirebaseSync(array $batchData)
    {
        foreach ($batchData as $couponData) {
            try {
                $coupon = Coupon::where('code', $couponData['code'])->first();
                if ($coupon) {
                    SyncCouponToFirebase::dispatch($coupon, 'create')
                        ->delay(now()->addSeconds(rand(5, 15))) // Stagger the jobs
                        ->onQueue('firebase-sync');
                }
            } catch (Exception $e) {
                Log::error("Failed to queue sync for coupon {$couponData['code']}: " . $e->getMessage());
            }
        }
    }

    /**
     * Optimized cache clearing
     */
    private function clearSpecificCache(string $code = null)
    {
        try {
            if ($code) {
                Cache::forget("coupon_{$code}");
                Cache::forget("coupon_usages_{$code}");
            }
            
            Cache::forget('coupon_statistics');
            Cache::forget('total_coupons_count');
            
        } catch (Exception $e) {
            Log::warning('Failed to clear coupon cache: ' . $e->getMessage());
        }
    }

    /**
     * Get coupon statistics with caching
     */
    public function getCouponStatistics()
    {
        try {
            return Cache::remember('coupon_statistics', config('coupon.statistics_cache_ttl', 600), function () {
                return [
                    'total_coupons' => Coupon::count(),
                    'active_coupons' => Coupon::where('status', Coupon::STATUS_ENABLED)
                                             ->where('expires_at', '>', now())
                                             ->count(),
                    'expired_coupons' => Coupon::where('expires_at', '<=', now())->count(),
                    'disabled_coupons' => Coupon::where('status', Coupon::STATUS_DISABLED)->count(),
                    'total_usages' => CouponUsage::count(),
                    'total_discount_given' => CouponUsage::sum('discount_amount') ?? 0,
                    'average_discount' => CouponUsage::avg('discount_amount') ?? 0,
                    'recent_usages' => CouponUsage::where('used_at', '>=', now()->subDays(30))->count(),
                    'expiring_soon' => Coupon::where('expires_at', '<=', now()->addDays(7))
                                           ->where('expires_at', '>', now())
                                           ->count(),
                    'unsynced_count' => Coupon::where('firebase_synced', false)->count()
                ];
            });

        } catch (Exception $e) {
            Log::error('Error getting coupon statistics: ' . $e->getMessage());
            return [
                'total_coupons' => 0,
                'active_coupons' => 0,
                'expired_coupons' => 0,
                'disabled_coupons' => 0,
                'total_usages' => 0,
                'total_discount_given' => 0,
                'average_discount' => 0,
                'recent_usages' => 0,
                'expiring_soon' => 0,
                'unsynced_count' => 0
            ];
        }
    }

    /**
     * Validate coupon
     */
    public function validateCoupon(string $code, string $userId, array $context = [])
    {
        try {
            $coupon = $this->getCouponByCode($code);

            if (!$coupon) {
                return [
                    'valid' => false,
                    'message' => 'Coupon not found',
                    'coupon' => null
                ];
            }

            // Check if coupon is active
            if (!$coupon->is_active) {
                return [
                    'valid' => false,
                    'message' => 'Coupon is not active',
                    'coupon' => $coupon
                ];
            }

            // Check user eligibility
            $userCheck = $coupon->canBeUsedBy($userId);
            if (!$userCheck['valid']) {
                return [
                    'valid' => false,
                    'message' => $userCheck['reason'],
                    'coupon' => $coupon
                ];
            }

            return [
                'valid' => true,
                'message' => 'Coupon is valid',
                'coupon' => $coupon
            ];

        } catch (Exception $e) {
            Log::error('Error validating coupon: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Error validating coupon',
                'coupon' => null
            ];
        }
    }

    /**
     * Generate unique coupon code
     */
    public function generateCouponCode($prefix = '', $length = 8)
    {
        do {
            $code = $prefix . strtoupper(Str::random($length));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    /**
     * Update coupon status
     */
    public function updateCouponStatus(string $code, string $status, string $updatedBy = null)
    {
        return $this->updateCoupon($code, [
            'status' => $status,
            'updated_by' => $updatedBy ?? 'system'
        ]);
    }

    /**
     * Perform bulk actions
     */
    public function performBulkAction(string $action, array $couponCodes)
    {
        try {
            $processed = 0;
            $failed = 0;

            foreach ($couponCodes as $code) {
                $success = false;

                switch ($action) {
                    case 'enable':
                        $success = $this->updateCouponStatus($code, Coupon::STATUS_ENABLED);
                        break;
                    case 'disable':
                        $success = $this->updateCouponStatus($code, Coupon::STATUS_DISABLED);
                        break;
                    case 'delete':
                        $success = $this->deleteCoupon($code);
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
     * Export coupons
     */
    public function exportCoupons(array $filters = [])
    {
        try {
            $coupons = $this->getAllCoupons($filters);
            
            return $coupons->map(function ($coupon) {
                return [
                    'code' => $coupon->code,
                    'description' => $coupon->description,
                    'coupon_type' => $coupon->coupon_type,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => $coupon->discount_value,
                    'minimum_amount' => $coupon->minimum_amount,
                    'maximum_discount' => $coupon->maximum_discount,
                    'usage_limit' => $coupon->usage_limit,
                    'used_count' => $coupon->used_count,
                    'user_usage_limit' => $coupon->user_usage_limit,
                    'starts_at' => $coupon->starts_at?->format('Y-m-d H:i:s'),
                    'expires_at' => $coupon->expires_at?->format('Y-m-d H:i:s'),
                    'status' => $coupon->status,
                    'is_active' => $coupon->is_active ? 'Yes' : 'No',
                    'firebase_synced' => $coupon->firebase_synced ? 'Yes' : 'No',
                    'created_at' => $coupon->created_at?->format('Y-m-d H:i:s')
                ];
            })->toArray();

        } catch (Exception $e) {
            Log::error('Error exporting coupons: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get coupon usage history
     */
    public function getCouponUsageHistory(string $code, array $filters = [])
    {
        try {
            $query = CouponUsage::where('coupon_code', $code);
            $limit = min($filters['limit'] ?? 50, 100);
            
            return $query->orderBy('used_at', 'desc')
                         ->limit($limit)
                         ->get();

        } catch (Exception $e) {
            Log::error('Error getting coupon usage history: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get total coupons count with caching
     */
    public function getTotalCouponsCount()
    {
        try {
            return Cache::remember('total_coupons_count', config('coupon.cache_ttl', 300), function () {
                return Coupon::count();
            });
        } catch (Exception $e) {
            Log::error('Error getting total coupons count: ' . $e->getMessage());
            return 0;
        }
    }
}