<?php
// app/Modules/Complaint/Services/ComplaintService.php

namespace App\Modules\Complaint\Services;

use App\Modules\Complaint\Models\Complaint;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class ComplaintService
{
    protected $firestoreService;

    public function __construct(FirestoreService $firestoreService)
    {
        $this->firestoreService = $firestoreService;
    }

    /**
     * Get all complaints from Firestore with filters and pagination
     */
   public function getAllComplaints($filters = [])
{
    try {
        Log::info('Fetching complaints from Firestore', ['filters' => $filters]);

        // Get all documents from the complaints collection
        $documents = $this->firestoreService->collection('complaints')->getAll();

        if (empty($documents)) {
            Log::info('No complaints found in Firestore');
            return collect();
        }

        Log::info('Found ' . count($documents) . ' complaints in Firestore');

        // DEBUG: Log the first document structure
        if (!empty($documents)) {
            Log::info('First Firestore document structure', [
                'first_doc' => $documents[0],
                'keys' => array_keys($documents[0] ?? [])
            ]);
        }

        // Convert Firestore documents to Complaint models
        $complaints = collect($documents)->map(function ($doc) {
            Log::info('Converting document to Complaint model', [
                'doc_keys' => array_keys($doc),
                'doc_id' => $doc['id'] ?? 'NO_ID',
                'doc_data' => $doc
            ]);
            
            $complaint = Complaint::fromFirestoreData($doc);
            
            Log::info('Created complaint object', [
                'complaint_id' => $complaint->id ?? 'NO_ID',
                'complaint_type' => get_class($complaint),
                'complaint_props' => array_keys(get_object_vars($complaint))
            ]);
            
            return $complaint;
        });

        // More logging...
        
        return $complaints;

    } catch (Exception $e) {
        Log::error('Error fetching complaints from Firestore: ' . $e->getMessage());
        return collect();
    }
}

    /**
     * Get a single complaint by ID
     */
    public function getComplaintById(string $id)
    {
        try {
            $cacheKey = "complaint_{$id}";
            
            return Cache::remember($cacheKey, config('complaint.cache_ttl', 300), function () use ($id) {
                $document = $this->firestoreService->collection('complaints')->find($id);
                
                if (!$document) {
                    return null;
                }

                return Complaint::fromFirestoreData($document);
            });

        } catch (Exception $e) {
            Log::error('Error fetching complaint by ID: ' . $e->getMessage(), ['id' => $id]);
            return null;
        }
    }

    /**
     * Get complaint statistics
     */
    public function getComplaintStatistics()
    {
        try {
            $cacheKey = 'complaint_statistics';
            
            return Cache::remember($cacheKey, config('complaint.statistics_cache_ttl', 600), function () {
                $complaints = $this->getAllComplaints(['limit' => 1000]); // Get more for stats
                
                $stats = [
                    'total_complaints' => $complaints->count(),
                    'pending_complaints' => $complaints->where('status', Complaint::STATUS_PENDING)->count(),
                    'in_progress_complaints' => $complaints->where('status', Complaint::STATUS_IN_PROGRESS)->count(),
                    'resolved_complaints' => $complaints->where('status', Complaint::STATUS_RESOLVED)->count(),
                    'closed_complaints' => $complaints->where('status', Complaint::STATUS_CLOSED)->count(),
                    'urgent_complaints' => $complaints->where('priority', Complaint::PRIORITY_URGENT)->count(),
                    'high_priority_complaints' => $complaints->where('priority', Complaint::PRIORITY_HIGH)->count(),
                    'overdue_complaints' => $complaints->filter(function ($complaint) {
                        return $complaint->is_overdue;
                    })->count(),
                    'recent_complaints' => $complaints->where('created_at', '>=', now()->subDays(7))->count(),
                    'ride_complaints' => $complaints->where('order_type', Complaint::ORDER_TYPE_RIDE)->count(),
                    'delivery_complaints' => $complaints->where('order_type', Complaint::ORDER_TYPE_DELIVERY)->count(),
                ];

                // Calculate average resolution time for resolved complaints
                $resolvedComplaints = $complaints->where('status', Complaint::STATUS_RESOLVED)
                    ->whereNotNull('resolved_at')
                    ->whereNotNull('created_at');

                if ($resolvedComplaints->count() > 0) {
                    $totalResolutionTime = $resolvedComplaints->sum(function ($complaint) {
                        return $complaint->created_at->diffInHours($complaint->resolved_at);
                    });
                    $stats['avg_resolution_hours'] = round($totalResolutionTime / $resolvedComplaints->count(), 2);
                } else {
                    $stats['avg_resolution_hours'] = 0;
                }

                return $stats;
            });

        } catch (Exception $e) {
            Log::error('Error getting complaint statistics: ' . $e->getMessage());
            return [
                'total_complaints' => 0,
                'pending_complaints' => 0,
                'in_progress_complaints' => 0,
                'resolved_complaints' => 0,
                'closed_complaints' => 0,
                'urgent_complaints' => 0,
                'high_priority_complaints' => 0,
                'overdue_complaints' => 0,
                'recent_complaints' => 0,
                'ride_complaints' => 0,
                'delivery_complaints' => 0,
                'avg_resolution_hours' => 0
            ];
        }
    }

    /**
     * Update complaint status
     */
  public function updateComplaintStatus(string $id, string $status, string $updatedBy = null)
{
    try {
        Log::info('=== Starting updateComplaintStatus ===', [
            'id' => $id,
            'status' => $status,
            'updatedBy' => $updatedBy,
            'collection' => 'complaints'
        ]);

        $complaint = $this->getComplaintById($id);
        
        if (!$complaint) {
            Log::error('Complaint not found for update', ['id' => $id]);
            return false;
        }

        Log::info('Found complaint to update', [
            'id' => $id,
            'current_status' => $complaint->status,
            'new_status' => $status
        ]);

        $updateData = [
            'status' => $status,
            'updated_at' => now()->toISOString()
        ];

        // If resolving the complaint, set resolved timestamp
        if ($status === Complaint::STATUS_RESOLVED && !$complaint->resolved_at) {
            $updateData['resolved_at'] = now()->toISOString();
            $updateData['resolved_by'] = $updatedBy ?? 'system';
        }

        Log::info('About to call Firestore update', [
            'id' => $id,
            'updateData' => $updateData,
            'firestore_service_class' => get_class($this->firestoreService)
        ]);

        $result = $this->firestoreService->collection('complaints')->update($id, $updateData);

        Log::info('Firestore update completed', [
            'id' => $id,
            'result' => $result,
            'result_type' => gettype($result)
        ]);

        if ($result) {
            // Clear cache
            Cache::forget("complaint_{$id}");
            Cache::forget('complaint_statistics');
            
            Log::info('=== Complaint status updated successfully ===', [
                'complaint_id' => $id,
                'status' => $status,
                'updated_by' => $updatedBy
            ]);
        } else {
            Log::error('Firestore update returned false', ['id' => $id]);
        }

        return $result;

    } catch (Exception $e) {
        Log::error('=== Error updating complaint status ===', [
            'id' => $id,
            'status' => $status,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}

    /**
     * Add admin notes to complaint
     */
    public function addAdminNotes(string $id, string $notes, string $updatedBy = null)
    {
        try {
            $complaint = $this->getComplaintById($id);
            
            if (!$complaint) {
                return false;
            }

            $existingNotes = $complaint->admin_notes ?? '';
            $timestamp = now()->format('Y-m-d H:i:s');
            $adminName = $updatedBy ?? 'Admin';
            
            $newNote = "\n[{$timestamp}] {$adminName}: {$notes}";
            $updatedNotes = $existingNotes . $newNote;

            $updateData = [
                'admin_notes' => $updatedNotes,
                'updated_at' => now()->toISOString()
            ];

            $result = $this->firestoreService->collection('complaints')->update($id, $updateData);

            if ($result) {
                Cache::forget("complaint_{$id}");
                
                Log::info('Admin notes added to complaint', [
                    'complaint_id' => $id,
                    'updated_by' => $updatedBy
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error adding admin notes: ' . $e->getMessage(), [
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Get total complaints count
     */
    public function getTotalComplaintsCount()
    {
        try {
            return Cache::remember('total_complaints_count', config('complaint.cache_ttl', 300), function () {
                return $this->firestoreService->collection('complaints')->count();
            });
        } catch (Exception $e) {
            Log::error('Error getting total complaints count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Bulk update complaint status
     */
    public function bulkUpdateStatus(array $complaintIds, string $status, string $updatedBy = null)
    {
        try {
            $processed = 0;
            $failed = 0;

            foreach ($complaintIds as $id) {
                if ($this->updateComplaintStatus($id, $status, $updatedBy)) {
                    $processed++;
                } else {
                    $failed++;
                }
            }

            return [
                'success' => true,
                'processed' => $processed,
                'failed' => $failed
            ];

        } catch (Exception $e) {
            Log::error('Error in bulk status update: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processed' => 0,
                'failed' => count($complaintIds)
            ];
        }
    }

    /**
     * Export complaints data
     */
    public function exportComplaints(array $filters = [])
    {
        try {
            $complaints = $this->getAllComplaints(array_merge($filters, ['limit' => 1000]));
            
            return $complaints->map(function ($complaint) {
                return [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'description' => $complaint->description,
                    'order_type' => $complaint->order_type,
                    'status' => $complaint->status,
                    'priority' => $complaint->priority,
                    'category' => $complaint->category,
                    'complaint_by' => $complaint->complaint_by,
                    'driver_name' => $complaint->driver_name,
                    'user_name' => $complaint->user_name,
                    'contact_info' => $complaint->contact_info,
                    'order_id' => $complaint->order_id,
                    'created_at' => $complaint->created_at?->format('Y-m-d H:i:s'),
                    'resolved_at' => $complaint->resolved_at?->format('Y-m-d H:i:s'),
                    'resolved_by' => $complaint->resolved_by,
                    'admin_notes' => $complaint->admin_notes
                ];
            })->toArray();

        } catch (Exception $e) {
            Log::error('Error exporting complaints: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Apply filters to complaints collection
     */
    private function applyFilters($complaints, $filters)
    {
        // Search filter
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $complaints = $complaints->filter(function ($complaint) use ($search) {
                return str_contains(strtolower($complaint->title ?? ''), $search) ||
                       str_contains(strtolower($complaint->description ?? ''), $search) ||
                       str_contains(strtolower($complaint->driver_name ?? ''), $search) ||
                       str_contains(strtolower($complaint->user_name ?? ''), $search) ||
                       str_contains(strtolower($complaint->id ?? ''), $search);
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $complaints = $complaints->where('status', $filters['status']);
        }

        // Priority filter
        if (!empty($filters['priority'])) {
            $complaints = $complaints->where('priority', $filters['priority']);
        }

        // Order type filter
        if (!empty($filters['order_type'])) {
            $complaints = $complaints->where('order_type', $filters['order_type']);
        }

        // Category filter
        if (!empty($filters['category'])) {
            $complaints = $complaints->where('category', $filters['category']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
            $complaints = $complaints->filter(function ($complaint) use ($dateFrom) {
                return $complaint->created_at && $complaint->created_at->gte($dateFrom);
            });
        }

        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
            $complaints = $complaints->filter(function ($complaint) use ($dateTo) {
                return $complaint->created_at && $complaint->created_at->lte($dateTo);
            });
        }

        // Overdue filter
        if (!empty($filters['overdue']) && $filters['overdue'] === 'true') {
            $complaints = $complaints->filter(function ($complaint) {
                return $complaint->is_overdue;
            });
        }

        return $complaints;
    }

    /**
     * Clear complaint cache
     */
    public function clearCache(string $id = null)
    {
        try {
            if ($id) {
                Cache::forget("complaint_{$id}");
            }
            
            Cache::forget('complaint_statistics');
            Cache::forget('total_complaints_count');
            
        } catch (Exception $e) {
            Log::warning('Failed to clear complaint cache: ' . $e->getMessage());
        }
    }

    /**
     * Health check for Firestore connection
     */
    public function healthCheck()
    {
        try {
            return $this->firestoreService->healthCheck();
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Complaint service health check failed: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ];
        }
    }
}