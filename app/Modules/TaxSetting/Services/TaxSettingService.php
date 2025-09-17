<?php
// app/Modules/TaxSetting/Services/TaxSettingService.php

namespace App\Modules\TaxSetting\Services;

use App\Modules\TaxSetting\Models\TaxSetting;
use App\Services\FirebaseSyncService;
use App\Jobs\SyncTaxSettingToFirebase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class TaxSettingService
{
    protected $firebaseSyncService;
    protected $autoSyncEnabled;
    protected $syncBatchSize;
    protected $immediateSyncThreshold;

    public function __construct(FirebaseSyncService $firebaseSyncService)
    {
        $this->firebaseSyncService = $firebaseSyncService;
        $this->autoSyncEnabled = config('taxsetting.auto_sync_firebase', true);
        $this->syncBatchSize = config('taxsetting.sync_batch_size', 10);
        $this->immediateSyncThreshold = config('taxsetting.immediate_sync_threshold', 5);
    }

    /**
     * Enhanced create tax setting with immediate sync option
     */
    public function createTaxSetting(array $data, bool $syncImmediately = false)
    {
        try {
            // Set default values
            $data['firebase_synced'] = false;

            // Create tax setting
            $taxSetting = TaxSetting::create($data);

            if ($taxSetting) {
                // Clear specific cache
                $this->clearSpecificCache($taxSetting->id);
                
                // Sync based on preference
                if ($syncImmediately || $this->shouldSyncImmediately()) {
                    $this->syncTaxSettingImmediately($taxSetting, 'create');
                } else {
                    $this->queueFirebaseSync($taxSetting, 'create');
                }

                Log::info('Tax setting created successfully', [
                    'tax_setting_id' => $taxSetting->id,
                    'created_by' => $data['created_by'] ?? 'system',
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
            }

            return $taxSetting;

        } catch (Exception $e) {
            Log::error('Error creating tax setting: ' . $e->getMessage(), [
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Enhanced update tax setting with sync options
     */
    public function updateTaxSetting(int $id, array $data, bool $syncImmediately = false)
    {
        try {
            $taxSetting = $this->getTaxSettingById($id);
            
            if (!$taxSetting) {
                return false;
            }

            // Mark as unsynced for Firebase update
            $data['firebase_synced'] = false;

            $result = $taxSetting->update($data);

            if ($result) {
                // Clear specific cache
                $this->clearSpecificCache($id);
                
                // Sync based on preference
                if ($syncImmediately || $this->shouldSyncImmediately()) {
                    $this->syncTaxSettingImmediately($taxSetting->fresh(), 'update');
                } else {
                    $this->queueFirebaseSync($taxSetting->fresh(), 'update');
                }

                Log::info('Tax setting updated successfully', [
                    'tax_setting_id' => $id,
                    'updated_by' => $data['updated_by'] ?? 'system',
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error updating tax setting: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Delete tax setting with sync
     */
    public function deleteTaxSetting(int $id)
    {
        try {
            $taxSetting = $this->getTaxSettingById($id);
            
            if (!$taxSetting) {
                return false;
            }

            $result = $taxSetting->delete();

            if ($result) {
                // Clear specific cache
                $this->clearSpecificCache($id);
                
                // Queue Firebase deletion
                $this->queueFirebaseSync($taxSetting, 'delete');

                Log::info('Tax setting deleted successfully', [
                    'tax_setting_id' => $id
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error deleting tax setting: ' . $e->getMessage(), [
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Calculate tax for given amount and context
     */
    public function calculateTax(int $taxSettingId, float $amount, array $context = [])
    {
        try {
            $taxSetting = $this->getTaxSettingById($taxSettingId);
            
            if (!$taxSetting || !$taxSetting->is_valid) {
                return [
                    'tax_amount' => 0,
                    'applicable' => false,
                    'total_amount' => $amount,
                    'breakdown' => null
                ];
            }

            $applicable = $taxSetting->appliesTo($context);
            $taxAmount = $applicable ? $taxSetting->calculateTax($amount, $context) : 0;

            return [
                'tax_amount' => $taxAmount,
                'applicable' => $applicable,
                'total_amount' => $amount + $taxAmount,
                'breakdown' => [
                    'base_amount' => $amount,
                    'tax_rate' => $taxSetting->rate,
                    'tax_type' => $taxSetting->tax_type,
                    'is_inclusive' => $taxSetting->is_inclusive
                ]
            ];

        } catch (Exception $e) {
            Log::error('Error calculating tax: ' . $e->getMessage());
            return [
                'tax_amount' => 0,
                'applicable' => false,
                'total_amount' => $amount,
                'breakdown' => null
            ];
        }
    }

    /**
     * Calculate taxes for multiple tax settings
     */
    public function calculateMultipleTaxes(array $taxSettingIds, float $amount, array $context = [])
    {
        $totalTax = 0;
        $breakdown = [];
        $baseAmount = $amount;

        foreach ($taxSettingIds as $taxSettingId) {
            $result = $this->calculateTax($taxSettingId, $baseAmount, $context);
            
            if ($result['applicable']) {
                $totalTax += $result['tax_amount'];
                $breakdown[] = [
                    'tax_setting_id' => $taxSettingId,
                    'tax_amount' => $result['tax_amount'],
                    'breakdown' => $result['breakdown']
                ];

                // For compound calculation, adjust base amount
                $taxSetting = $this->getTaxSettingById($taxSettingId);
                if ($taxSetting && $taxSetting->calculation_method === 'compound') {
                    $baseAmount += $result['tax_amount'];
                }
            }
        }

        return [
            'total_tax_amount' => $totalTax,
            'total_amount' => $amount + $totalTax,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Get all tax settings with filters
     */
    public function getAllTaxSettings($filters = [])
    {
        try {
            $query = TaxSetting::query();

            // Apply search filter
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply tax type filter
            if (!empty($filters['tax_type'])) {
                $query->where('tax_type', $filters['tax_type']);
            }

            // Apply calculation method filter
            if (!empty($filters['calculation_method'])) {
                $query->where('calculation_method', $filters['calculation_method']);
            }

            // Apply applicable to filter
            if (!empty($filters['applicable_to'])) {
                $query->where('applicable_to', $filters['applicable_to']);
            }

            // Apply active status filter
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'priority_order';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($sortBy, $sortDirection);

            // Apply limit
            $limit = min($filters['limit'] ?? 25, 100);
            
            return $query->limit($limit)->get();

        } catch (Exception $e) {
            Log::error('Error getting tax settings: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get tax setting by ID with caching
     */
    public function getTaxSettingById(int $id)
    {
        try {
            return Cache::remember("tax_setting_{$id}", config('taxsetting.cache_ttl', 300), function () use ($id) {
                return TaxSetting::find($id);
            });
        } catch (Exception $e) {
            Log::error('Error getting tax setting by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update tax setting status
     */
    public function updateTaxSettingStatus(int $id, bool $isActive, string $updatedBy = null)
    {
        return $this->updateTaxSetting($id, [
            'is_active' => $isActive,
            'updated_by' => $updatedBy ?? 'system'
        ]);
    }

    /**
     * Perform bulk actions
     */
    public function performBulkAction(string $action, array $taxSettingIds)
    {
        try {
            $processed = 0;
            $failed = 0;

            foreach ($taxSettingIds as $id) {
                $success = false;

                switch ($action) {
                    case 'activate':
                        $success = $this->updateTaxSettingStatus($id, true);
                        break;
                    case 'deactivate':
                        $success = $this->updateTaxSettingStatus($id, false);
                        break;
                    case 'delete':
                        $success = $this->deleteTaxSetting($id);
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
     * Get tax setting statistics with caching
     */
    public function getTaxSettingStatistics()
    {
        try {
            return Cache::remember('tax_setting_statistics', config('taxsetting.statistics_cache_ttl', 600), function () {
                return [
                    'total_tax_settings' => TaxSetting::count(),
                    'active_tax_settings' => TaxSetting::where('is_active', true)->count(),
                    'inactive_tax_settings' => TaxSetting::where('is_active', false)->count(),
                    'percentage_tax_settings' => TaxSetting::where('tax_type', 'percentage')->count(),
                    'fixed_tax_settings' => TaxSetting::where('tax_type', 'fixed')->count(),
                    'hybrid_tax_settings' => TaxSetting::where('tax_type', 'hybrid')->count(),
                    'expiring_soon' => TaxSetting::where('expires_at', '<=', now()->addDays(7))
                                               ->where('expires_at', '>', now())
                                               ->count(),
                    'unsynced_count' => TaxSetting::where('firebase_synced', false)->count()
                ];
            });

        } catch (Exception $e) {
            Log::error('Error getting tax setting statistics: ' . $e->getMessage());
            return [
                'total_tax_settings' => 0,
                'active_tax_settings' => 0,
                'inactive_tax_settings' => 0,
                'percentage_tax_settings' => 0,
                'fixed_tax_settings' => 0,
                'hybrid_tax_settings' => 0,
                'expiring_soon' => 0,
                'unsynced_count' => 0
            ];
        }
    }

    /**
     * Get total tax settings count with caching
     */
    public function getTotalTaxSettingsCount()
    {
        try {
            return Cache::remember('total_tax_settings_count', config('taxsetting.cache_ttl', 300), function () {
                return TaxSetting::count();
            });
        } catch (Exception $e) {
            Log::error('Error getting total tax settings count: ' . $e->getMessage());
            return 0;
        }
    }

    // ============ FIREBASE SYNC METHODS ============

    /**
     * Immediate sync for critical operations
     */
    private function syncTaxSettingImmediately($taxSetting, $action)
    {
        try {
            $this->firebaseSyncService->syncModel($taxSetting, $action);
            
            if (method_exists($taxSetting, 'update') && $action !== 'delete') {
                $taxSetting->update([
                    'firebase_synced' => true,
                    'firebase_synced_at' => now()
                ]);
            }
            
            Log::info('Immediate Firebase sync completed', [
                'tax_setting_id' => $taxSetting->id,
                'action' => $action
            ]);
            
        } catch (Exception $e) {
            Log::error('Immediate Firebase sync failed', [
                'tax_setting_id' => $taxSetting->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to queue if immediate sync fails
            $this->queueFirebaseSync($taxSetting, $action);
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
        
        $pendingCount = TaxSetting::where('firebase_synced', false)->count();
        
        return $pendingCount < $this->immediateSyncThreshold;
    }

    /**
     * Queue Firebase sync using Laravel Jobs
     */
    private function queueFirebaseSync($taxSetting, $action)
    {
        try {
            SyncTaxSettingToFirebase::dispatch($taxSetting, $action)
                ->delay(now()->addSeconds(2))
                ->onQueue('firebase-sync');
            
            Log::info('Firebase sync job queued', [
                'tax_setting_id' => $taxSetting->id,
                'action' => $action
            ]);
            
        } catch (Exception $e) {
            Log::warning('Failed to queue Firebase sync job: ' . $e->getMessage());
            
            // Fallback: try immediate sync but don't block if it fails
            try {
                $this->firebaseSyncService->syncModel($taxSetting, $action);
                if (method_exists($taxSetting, 'update') && $action !== 'delete') {
                    $taxSetting->update(['firebase_synced' => true]);
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

            $pendingTaxSettings = TaxSetting::where('firebase_synced', false)
                                           ->orderBy('created_at', 'asc')
                                           ->limit($this->syncBatchSize)
                                           ->get();

            if ($pendingTaxSettings->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No pending syncs',
                    'processed' => 0
                ];
            }

            $processed = 0;
            $failed = 0;

            foreach ($pendingTaxSettings as $taxSetting) {
                try {
                    $this->firebaseSyncService->syncModel($taxSetting, 'create');
                    $taxSetting->update([
                        'firebase_synced' => true,
                        'firebase_synced_at' => now()
                    ]);
                    $processed++;
                    
                    // Small delay to prevent overwhelming Firebase
                    usleep(100000); // 0.1 second
                    
                } catch (Exception $e) {
                    $failed++;
                    Log::error("Auto-sync failed for tax setting {$taxSetting->id}: " . $e->getMessage());
                }
            }

            Log::info('Auto-sync completed', [
                'processed' => $processed,
                'failed' => $failed,
                'batch_size' => $this->syncBatchSize
            ]);

            return [
                'success' => true,
                'message' => "Auto-sync processed {$processed} tax settings",
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
                'total_tax_settings' => TaxSetting::count(),
                'synced_tax_settings' => TaxSetting::where('firebase_synced', true)->count(),
                'pending_syncs' => TaxSetting::where('firebase_synced', false)->count(),
                'old_pending' => TaxSetting::where('firebase_synced', false)
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
                'total_tax_settings' => 0,
                'synced_tax_settings' => 0,
                'pending_syncs' => 0,
                'old_pending' => 0
            ];
        }
    }

    /**
     * Force sync all unsynced tax settings
     */
    public function forceSyncAll()
    {
        try {
            $totalUnsynced = TaxSetting::where('firebase_synced', false)->count();
            
            if ($totalUnsynced === 0) {
                return [
                    'success' => true,
                    'message' => 'All tax settings are already synced',
                    'processed' => 0
                ];
            }

            // Process in batches to prevent memory issues
            $batchSize = 20;
            $totalProcessed = 0;
            $totalFailed = 0;

            do {
                $batch = TaxSetting::where('firebase_synced', false)
                                  ->limit($batchSize)
                                  ->get();

                foreach ($batch as $taxSetting) {
                    try {
                        $this->firebaseSyncService->syncModel($taxSetting, 'create');
                        $taxSetting->update([
                            'firebase_synced' => true,
                            'firebase_synced_at' => now()
                        ]);
                        $totalProcessed++;
                    } catch (Exception $e) {
                        $totalFailed++;
                        Log::error("Force sync failed for {$taxSetting->id}: " . $e->getMessage());
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
     * Optimized cache clearing
     */
    private function clearSpecificCache(int $id = null)
    {
        try {
            if ($id) {
                Cache::forget("tax_setting_{$id}");
            }
            
            Cache::forget('tax_setting_statistics');
            Cache::forget('total_tax_settings_count');
            
        } catch (Exception $e) {
            Log::warning('Failed to clear tax setting cache: ' . $e->getMessage());
        }
    }
}