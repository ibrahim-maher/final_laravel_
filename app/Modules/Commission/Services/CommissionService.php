<?php
// app/Modules/Commission/Services/CommissionService.php

namespace App\Modules\Commission\Services;

use App\Modules\Commission\Models\Commission;
use App\Modules\Commission\Models\CommissionPayout;
use App\Services\FirebaseSyncService;
use App\Jobs\SyncCommissionToFirebase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CommissionService
{
    protected $firebaseSyncService;
    protected $autoSyncEnabled;
    protected $syncBatchSize;
    protected $immediateSyncThreshold;

    public function __construct(FirebaseSyncService $firebaseSyncService)
    {
        $this->firebaseSyncService = $firebaseSyncService;
        $this->autoSyncEnabled = config('commission.auto_sync_firebase', true);
        $this->syncBatchSize = config('commission.sync_batch_size', 10);
        $this->immediateSyncThreshold = config('commission.immediate_sync_threshold', 5);
    }

    /**
     * Enhanced create commission with immediate sync option
     */
    public function createCommission(array $data, bool $syncImmediately = false)
    {
        try {
            // Set default values
            $data['firebase_synced'] = false;

            // Create commission
            $commission = Commission::create($data);

            if ($commission) {
                // Clear specific cache
                $this->clearSpecificCache($commission->id);
                
                // Sync based on preference
                if ($syncImmediately || $this->shouldSyncImmediately()) {
                    $this->syncCommissionImmediately($commission, 'create');
                } else {
                    $this->queueFirebaseSync($commission, 'create');
                }

                Log::info('Commission created successfully', [
                    'commission_id' => $commission->id,
                    'created_by' => $data['created_by'] ?? 'system',
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
            }

            return $commission;

        } catch (Exception $e) {
            Log::error('Error creating commission: ' . $e->getMessage(), [
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Enhanced update commission with sync options
     */
    public function updateCommission(int $id, array $data, bool $syncImmediately = false)
    {
        try {
            $commission = $this->getCommissionById($id);
            
            if (!$commission) {
                return false;
            }

            // Mark as unsynced for Firebase update
            $data['firebase_synced'] = false;

            $result = $commission->update($data);

            if ($result) {
                // Clear specific cache
                $this->clearSpecificCache($id);
                
                // Sync based on preference
                if ($syncImmediately || $this->shouldSyncImmediately()) {
                    $this->syncCommissionImmediately($commission->fresh(), 'update');
                } else {
                    $this->queueFirebaseSync($commission->fresh(), 'update');
                }

                Log::info('Commission updated successfully', [
                    'commission_id' => $id,
                    'updated_by' => $data['updated_by'] ?? 'system',
                    'sync_method' => $syncImmediately ? 'immediate' : 'queued'
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error updating commission: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Delete commission with sync
     */
    public function deleteCommission(int $id)
    {
        try {
            $commission = $this->getCommissionById($id);
            
            if (!$commission) {
                return false;
            }

            $result = $commission->delete();

            if ($result) {
                // Clear specific cache
                $this->clearSpecificCache($id);
                
                // Queue Firebase deletion
                $this->queueFirebaseSync($commission, 'delete');

                Log::info('Commission deleted successfully', [
                    'commission_id' => $id
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error deleting commission: ' . $e->getMessage(), [
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Calculate commission for given amount and context
     */
    public function calculateCommission(int $commissionId, float $amount, array $context = [])
    {
        try {
            $commission = $this->getCommissionById($commissionId);
            
            if (!$commission || !$commission->is_valid) {
                return [
                    'commission_amount' => 0,
                    'applicable' => false,
                    'net_amount' => $amount,
                    'breakdown' => null
                ];
            }

            $applicable = $commission->appliesTo($context);
            $commissionAmount = $applicable ? $commission->calculateCommission($amount, $context) : 0;

            return [
                'commission_amount' => $commissionAmount,
                'applicable' => $applicable,
                'net_amount' => $amount - $commissionAmount,
                'breakdown' => [
                    'gross_amount' => $amount,
                    'commission_rate' => $commission->rate,
                    'commission_type' => $commission->commission_type,
                    'recipient_type' => $commission->recipient_type,
                    'calculation_method' => $commission->calculation_method
                ]
            ];

        } catch (Exception $e) {
            Log::error('Error calculating commission: ' . $e->getMessage());
            return [
                'commission_amount' => 0,
                'applicable' => false,
                'net_amount' => $amount,
                'breakdown' => null
            ];
        }
    }

    /**
     * Calculate commissions for multiple commission settings
     */
    public function calculateMultipleCommissions(array $commissionIds, float $amount, array $context = [])
    {
        $totalCommission = 0;
        $breakdown = [];
        $netAmount = $amount;

        foreach ($commissionIds as $commissionId) {
            $result = $this->calculateCommission($commissionId, $amount, $context);
            
            if ($result['applicable']) {
                $totalCommission += $result['commission_amount'];
                $breakdown[] = [
                    'commission_id' => $commissionId,
                    'commission_amount' => $result['commission_amount'],
                    'breakdown' => $result['breakdown']
                ];
            }
        }

        return [
            'total_commission_amount' => $totalCommission,
            'net_amount' => $amount - $totalCommission,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Process commission payout
     */
    public function processCommissionPayout(int $commissionId, string $recipientId, float $amount, array $context = [])
    {
        try {
            $commission = $this->getCommissionById($commissionId);
            
            if (!$commission || !$commission->is_valid) {
                throw new Exception('Invalid commission');
            }

            // Check minimum payout amount
            if ($commission->minimum_payout_amount && $amount < $commission->minimum_payout_amount) {
                throw new Exception('Amount below minimum payout threshold');
            }

            $payout = CommissionPayout::create([
                'commission_id' => $commissionId,
                'recipient_id' => $recipientId,
                'recipient_type' => $commission->recipient_type,
                'amount' => $amount,
                'payout_method' => $context['payout_method'] ?? 'automatic',
                'payout_date' => now(),
                'status' => 'processed',
                'metadata' => $context
            ]);

            Log::info('Commission payout processed', [
                'commission_id' => $commissionId,
                'recipient_id' => $recipientId,
                'amount' => $amount,
                'payout_id' => $payout->id
            ]);

            return $payout;

        } catch (Exception $e) {
            Log::error('Error processing commission payout: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all commissions with filters
     */
    public function getAllCommissions($filters = [])
    {
        try {
            $query = Commission::query();

            // Apply search filter
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply commission type filter
            if (!empty($filters['commission_type'])) {
                $query->where('commission_type', $filters['commission_type']);
            }

            // Apply recipient type filter
            if (!empty($filters['recipient_type'])) {
                $query->where('recipient_type', $filters['recipient_type']);
            }

            // Apply calculation method filter
            if (!empty($filters['calculation_method'])) {
                $query->where('calculation_method', $filters['calculation_method']);
            }

            // Apply payment frequency filter
            if (!empty($filters['payment_frequency'])) {
                $query->where('payment_frequency', $filters['payment_frequency']);
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
            Log::error('Error getting commissions: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get commission by ID with caching
     */
    public function getCommissionById(int $id)
    {
        try {
            return Cache::remember("commission_{$id}", config('commission.cache_ttl', 300), function () use ($id) {
                return Commission::find($id);
            });
        } catch (Exception $e) {
            Log::error('Error getting commission by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get commission payout history
     */
    public function getCommissionPayoutHistory(int $commissionId, array $filters = [])
    {
        try {
            $query = CommissionPayout::where('commission_id', $commissionId);
            $limit = min($filters['limit'] ?? 50, 100);
            
            return $query->orderBy('payout_date', 'desc')
                         ->limit($limit)
                         ->get();

        } catch (Exception $e) {
            Log::error('Error getting commission payout history: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Update commission status
     */
    public function updateCommissionStatus(int $id, bool $isActive, string $updatedBy = null)
    {
        return $this->updateCommission($id, [
            'is_active' => $isActive,
            'updated_by' => $updatedBy ?? 'system'
        ]);
    }

    /**
     * Perform bulk actions
     */
    public function performBulkAction(string $action, array $commissionIds)
    {
        try {
            $processed = 0;
            $failed = 0;

            foreach ($commissionIds as $id) {
                $success = false;

                switch ($action) {
                    case 'activate':
                        $success = $this->updateCommissionStatus($id, true);
                        break;
                    case 'deactivate':
                        $success = $this->updateCommissionStatus($id, false);
                        break;
                    case 'delete':
                        $success = $this->deleteCommission($id);
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
     * Get commission statistics with caching
     */
    public function getCommissionStatistics()
    {
        try {
            return Cache::remember('commission_statistics', config('commission.statistics_cache_ttl', 600), function () {
                return [
                    'total_commissions' => Commission::count(),
                    'active_commissions' => Commission::where('is_active', true)->count(),
                    'inactive_commissions' => Commission::where('is_active', false)->count(),
                    'driver_commissions' => Commission::where('recipient_type', 'driver')->count(),
                    'company_commissions' => Commission::where('recipient_type', 'company')->count(),
                    'partner_commissions' => Commission::where('recipient_type', 'partner')->count(),
                    'total_payouts' => CommissionPayout::count(),
                    'total_payout_amount' => CommissionPayout::sum('amount') ?? 0,
                    'pending_payouts' => CommissionPayout::where('status', 'pending')->count(),
                    'processed_payouts' => CommissionPayout::where('status', 'processed')->count(),
                    'unsynced_count' => Commission::where('firebase_synced', false)->count()
                ];
            });

        } catch (Exception $e) {
            Log::error('Error getting commission statistics: ' . $e->getMessage());
            return [
                'total_commissions' => 0,
                'active_commissions' => 0,
                'inactive_commissions' => 0,
                'driver_commissions' => 0,
                'company_commissions' => 0,
                'partner_commissions' => 0,
                'total_payouts' => 0,
                'total_payout_amount' => 0,
                'pending_payouts' => 0,
                'processed_payouts' => 0,
                'unsynced_count' => 0
            ];
        }
    }

    /**
     * Get total commissions count with caching
     */
    public function getTotalCommissionsCount()
    {
        try {
            return Cache::remember('total_commissions_count', config('commission.cache_ttl', 300), function () {
                return Commission::count();
            });
        } catch (Exception $e) {
            Log::error('Error getting total commissions count: ' . $e->getMessage());
            return 0;
        }
    }

    // ============ FIREBASE SYNC METHODS ============

    /**
     * Immediate sync for critical operations
     */
    private function syncCommissionImmediately($commission, $action)
    {
        try {
            $this->firebaseSyncService->syncModel($commission, $action);
            
            if (method_exists($commission, 'update') && $action !== 'delete') {
                $commission->update([
                    'firebase_synced' => true,
                    'firebase_synced_at' => now()
                ]);
            }
            
            Log::info('Immediate Firebase sync completed', [
                'commission_id' => $commission->id,
                'action' => $action
            ]);
            
        } catch (Exception $e) {
            Log::error('Immediate Firebase sync failed', [
                'commission_id' => $commission->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to queue if immediate sync fails
            $this->queueFirebaseSync($commission, $action);
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
        
        $pendingCount = Commission::where('firebase_synced', false)->count();
        
        return $pendingCount < $this->immediateSyncThreshold;
    }

    /**
     * Queue Firebase sync using Laravel Jobs
     */
    private function queueFirebaseSync($commission, $action)
    {
        try {
            SyncCommissionToFirebase::dispatch($commission, $action)
                ->delay(now()->addSeconds(2))
                ->onQueue('firebase-sync');
            
            Log::info('Firebase sync job queued', [
                'commission_id' => $commission->id,
                'action' => $action
            ]);
            
        } catch (Exception $e) {
            Log::warning('Failed to queue Firebase sync job: ' . $e->getMessage());
            
            // Fallback: try immediate sync but don't block if it fails
            try {
                $this->firebaseSyncService->syncModel($commission, $action);
                if (method_exists($commission, 'update') && $action !== 'delete') {
                    $commission->update(['firebase_synced' => true]);
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

            $pendingCommissions = Commission::where('firebase_synced', false)
                                           ->orderBy('created_at', 'asc')
                                           ->limit($this->syncBatchSize)
                                           ->get();

            if ($pendingCommissions->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No pending syncs',
                    'processed' => 0
                ];
            }

            $processed = 0;
            $failed = 0;

            foreach ($pendingCommissions as $commission) {
                try {
                    $this->firebaseSyncService->syncModel($commission, 'create');
                    $commission->update([
                        'firebase_synced' => true,
                        'firebase_synced_at' => now()
                    ]);
                    $processed++;
                    
                    usleep(100000); // 0.1 second delay
                    
                } catch (Exception $e) {
                    $failed++;
                    Log::error("Auto-sync failed for commission {$commission->id}: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => "Auto-sync processed {$processed} commissions",
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
     * Check sync health
     */
    public function checkSyncHealth()
    {
        try {
            return [
                'total_commissions' => Commission::count(),
                'synced_commissions' => Commission::where('firebase_synced', true)->count(),
                'pending_syncs' => Commission::where('firebase_synced', false)->count(),
                'old_pending' => Commission::where('firebase_synced', false)
                                         ->where('created_at', '<', now()->subHours(1))
                                         ->count()
            ];
        } catch (Exception $e) {
            Log::error('Sync health check failed: ' . $e->getMessage());
            return [
                'total_commissions' => 0,
                'synced_commissions' => 0,
                'pending_syncs' => 0,
                'old_pending' => 0
            ];
        }
    }

    /**
     * Force sync all unsynced commissions
     */
    public function forceSyncAll()
    {
        try {
            $totalUnsynced = Commission::where('firebase_synced', false)->count();
            
            if ($totalUnsynced === 0) {
                return [
                    'success' => true,
                    'message' => 'All commissions are already synced',
                    'processed' => 0
                ];
            }

            $batchSize = 20;
            $totalProcessed = 0;
            $totalFailed = 0;

            do {
                $batch = Commission::where('firebase_synced', false)
                                  ->limit($batchSize)
                                  ->get();

                foreach ($batch as $commission) {
                    try {
                        $this->firebaseSyncService->syncModel($commission, 'create');
                        $commission->update([
                            'firebase_synced' => true,
                            'firebase_synced_at' => now()
                        ]);
                        $totalProcessed++;
                    } catch (Exception $e) {
                        $totalFailed++;
                        Log::error("Force sync failed for {$commission->id}: " . $e->getMessage());
                    }
                    
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
                Cache::forget("commission_{$id}");
            }
            
            Cache::forget('commission_statistics');
            Cache::forget('total_commissions_count');
            
        } catch (Exception $e) {
            Log::warning('Failed to clear commission cache: ' . $e->getMessage());
        }
    }
}