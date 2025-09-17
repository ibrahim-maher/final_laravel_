<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\DriverActivity;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\Vehicle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DriverActivityService
{
    /**
     * Get driver activities with enhanced filtering
     */
    public function getDriverActivities(string $driverFirebaseUid, array $filters = [])
    {
        try {
            Log::info('Getting activities for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $query = DriverActivity::where('driver_firebase_uid', $driverFirebaseUid)
                                  ->with(['vehicle', 'ride', 'document']);

            // Apply filters
            $query = $this->applyFilters($query, $filters);

            $limit = $filters['limit'] ?? 50;
            
            return $query->orderBy('created_at', 'desc')->paginate($limit);
        } catch (\Exception $e) {
            Log::error('Error getting driver activities: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Create activity for driver with enhanced data
     */
    public function createActivity(string $driverFirebaseUid, string $activityType, array $activityData = []): ?DriverActivity
    {
        try {
            Log::info('Creating activity for driver', [
                'firebase_uid' => $driverFirebaseUid,
                'activity_type' => $activityType
            ]);
            
            // Prepare activity data with enhanced fields
            $activityRecord = $this->prepareActivityData($driverFirebaseUid, $activityType, $activityData);
            
            return DriverActivity::create($activityRecord);
        } catch (\Exception $e) {
            Log::error('Error creating activity: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get activity by ID
     */
    public function getActivityById($activityId): ?DriverActivity
    {
        return DriverActivity::with(['driver', 'vehicle', 'ride', 'document'])->find($activityId);
    }

    /**
     * Update activity
     */
    public function updateActivity($activityId, array $activityData): bool
    {
        try {
            Log::info('Updating activity', ['activity_id' => $activityId]);
            
            $activity = DriverActivity::find($activityId);
            if (!$activity) {
                return false;
            }

            return $activity->update($activityData);
        } catch (\Exception $e) {
            Log::error('Error updating activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete activity
     */
    public function deleteActivity($activityId): bool
    {
        try {
            Log::info('Deleting activity', ['activity_id' => $activityId]);
            
            $activity = DriverActivity::find($activityId);
            if (!$activity) {
                return false;
            }

            return $activity->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Archive activity
     */
    public function archiveActivity($activityId): bool
    {
        try {
            $activity = DriverActivity::find($activityId);
            if (!$activity) {
                return false;
            }

            $activity->archive();
            return true;
        } catch (\Exception $e) {
            Log::error('Error archiving activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get activities by type
     */
    public function getActivitiesByType(string $activityType, array $filters = [])
    {
        $query = DriverActivity::byType($activityType)->with(['driver', 'vehicle', 'ride', 'document']);
        
        $query = $this->applyFilters($query, $filters);
        
        $limit = $filters['limit'] ?? 50;
        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get activities by category
     */
    public function getActivitiesByCategory(string $category, array $filters = [])
    {
        $query = DriverActivity::byCategory($category)->with(['driver', 'vehicle', 'ride', 'document']);
        
        $query = $this->applyFilters($query, $filters);
        
        $limit = $filters['limit'] ?? 50;
        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get recent activities for driver
     */
    public function getRecentActivitiesForDriver(string $driverFirebaseUid, int $days = 7, int $limit = 50)
    {
        return DriverActivity::where('driver_firebase_uid', $driverFirebaseUid)
                             ->recent($days)
                             ->with(['vehicle', 'ride', 'document'])
                             ->orderBy('created_at', 'desc')
                             ->limit($limit)
                             ->get();
    }

    /**
     * Get activities in date range
     */
    public function getActivitiesInDateRange(string $dateFrom, string $dateTo, array $filters = [])
    {
        $query = DriverActivity::dateRange($dateFrom, $dateTo)->with(['driver', 'vehicle', 'ride', 'document']);
        
        $query = $this->applyFilters($query, $filters);
        
        $limit = $filters['limit'] ?? 100;
        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get activity statistics for driver
     */
    public function getDriverActivityStatistics(string $driverFirebaseUid): array
    {
        try {
            $baseQuery = DriverActivity::where('driver_firebase_uid', $driverFirebaseUid);
            
            $stats = [
                'total_activities' => $baseQuery->count(),
                'activities_by_type' => $baseQuery->select('activity_type', DB::raw('count(*) as count'))
                                                 ->groupBy('activity_type')
                                                 ->pluck('count', 'activity_type')
                                                 ->toArray(),
                'activities_by_category' => $baseQuery->select('activity_category', DB::raw('count(*) as count'))
                                                     ->groupBy('activity_category')
                                                     ->pluck('count', 'activity_category')
                                                     ->toArray(),
                'activities_today' => (clone $baseQuery)->today()->count(),
                'activities_this_week' => (clone $baseQuery)->thisWeek()->count(),
                'activities_this_month' => (clone $baseQuery)->thisMonth()->count(),
                'unread_activities' => (clone $baseQuery)->unread()->count(),
                'high_priority_activities' => (clone $baseQuery)->highPriority()->count(),
                'latest_activity' => (clone $baseQuery)->with(['vehicle', 'ride', 'document'])
                                                       ->latest()
                                                       ->first(),
                'most_active_day' => $this->getMostActiveDay($driverFirebaseUid)
            ];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting driver activity statistics: ' . $e->getMessage());
            return $this->getEmptyStats();
        }
    }

    /**
     * Get system-wide activity statistics
     */
    public function getSystemActivityStatistics(): array
    {
        try {
            $stats = [
                'total_activities' => DriverActivity::count(),
                'activities_by_type' => DriverActivity::select('activity_type', DB::raw('count(*) as count'))
                                                      ->groupBy('activity_type')
                                                      ->pluck('count', 'activity_type')
                                                      ->toArray(),
                'activities_by_category' => DriverActivity::select('activity_category', DB::raw('count(*) as count'))
                                                          ->groupBy('activity_category')
                                                          ->pluck('count', 'activity_category')
                                                          ->toArray(),
                'activities_today' => DriverActivity::today()->count(),
                'activities_this_week' => DriverActivity::thisWeek()->count(),
                'activities_this_month' => DriverActivity::thisMonth()->count(),
                'unread_activities' => DriverActivity::unread()->count(),
                'high_priority_activities' => DriverActivity::highPriority()->count(),
                'most_active_drivers' => $this->getMostActiveDrivers()
            ];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting system activity statistics: ' . $e->getMessage());
            return $this->getEmptySystemStats();
        }
    }

    /**
     * Mark activity as read
     */
    public function markActivityAsRead($activityId): bool
    {
        try {
            $activity = DriverActivity::find($activityId);
            if (!$activity) {
                return false;
            }

            $activity->markAsRead();
            return true;
        } catch (\Exception $e) {
            Log::error('Error marking activity as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark multiple activities as read
     */
    public function markActivitiesAsRead(array $activityIds): array
    {
        $results = [];
        
        try {
            $successCount = DriverActivity::markMultipleAsRead($activityIds);
            
            foreach ($activityIds as $id) {
                $results[$id] = true;
            }
            
            Log::info('Marked multiple activities as read', [
                'count' => $successCount,
                'activity_ids' => $activityIds
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error marking multiple activities as read: ' . $e->getMessage());
            
            foreach ($activityIds as $id) {
                $results[$id] = false;
            }
        }
        
        return $results;
    }

    /**
     * Get unread activities for driver
     */
    public function getUnreadActivitiesForDriver(string $driverFirebaseUid, int $limit = 50)
    {
        return DriverActivity::where('driver_firebase_uid', $driverFirebaseUid)
                             ->unread()
                             ->with(['vehicle', 'ride', 'document'])
                             ->orderBy('created_at', 'desc')
                             ->limit($limit)
                             ->get();
    }

    /**
     * Get unread activity count for driver
     */
    public function getUnreadActivityCount(string $driverFirebaseUid): int
    {
        return DriverActivity::where('driver_firebase_uid', $driverFirebaseUid)
                             ->unread()
                             ->count();
    }

    /**
     * Get vehicle activities
     */
    public function getVehicleActivities($vehicleId, array $filters = [])
    {
        $query = DriverActivity::forVehicle($vehicleId)->with(['driver', 'vehicle', 'ride', 'document']);
        
        $query = $this->applyFilters($query, $filters);
        
        $limit = $filters['limit'] ?? 50;
        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get ride activities
     */
    public function getRideActivities($rideId, array $filters = [])
    {
        $query = DriverActivity::forRide($rideId)->with(['driver', 'vehicle', 'ride', 'document']);
        
        $query = $this->applyFilters($query, $filters);
        
        $limit = $filters['limit'] ?? 50;
        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get document activities
     */
    public function getDocumentActivities($documentId, array $filters = [])
    {
        $query = DriverActivity::forDocument($documentId)->with(['driver', 'vehicle', 'ride', 'document']);
        
        $query = $this->applyFilters($query, $filters);
        
        $limit = $filters['limit'] ?? 50;
        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Bulk archive activities
     */
    public function bulkArchiveActivities(array $activityIds): int
    {
        try {
            return DriverActivity::archiveMultiple($activityIds);
        } catch (\Exception $e) {
            Log::error('Error bulk archiving activities: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up old activities
     */
    public function cleanupOldActivities(int $daysToKeep = 90): int
    {
        try {
            return DriverActivity::cleanupOldActivities($daysToKeep);
        } catch (\Exception $e) {
            Log::error('Error cleaning up old activities: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create location-based activity
     */
    public function createLocationActivity(string $driverFirebaseUid, $latitude, $longitude, $address = null): ?DriverActivity
    {
        return $this->createActivity($driverFirebaseUid, DriverActivity::TYPE_LOCATION_UPDATE, [
            'title' => 'Location Updated',
            'description' => $address ? "Location updated: {$address}" : 'Driver location updated',
            'location_latitude' => $latitude,
            'location_longitude' => $longitude,
            'location_address' => $address,
            'priority' => DriverActivity::PRIORITY_LOW
        ]);
    }

    /**
     * Create ride-related activity
     */
    public function createRideActivity(string $driverFirebaseUid, string $activityType, $rideId, array $additionalData = []): ?DriverActivity
    {
        return $this->createActivity($driverFirebaseUid, $activityType, array_merge([
            'ride_id' => $rideId,
            'related_entity_type' => 'ride',
            'related_entity_id' => $rideId
        ], $additionalData));
    }

    /**
     * Create vehicle-related activity
     */
    public function createVehicleActivity(string $driverFirebaseUid, string $activityType, $vehicleId, array $additionalData = []): ?DriverActivity
    {
        return $this->createActivity($driverFirebaseUid, $activityType, array_merge([
            'vehicle_id' => $vehicleId,
            'related_entity_type' => 'vehicle',
            'related_entity_id' => $vehicleId
        ], $additionalData));
    }

    /**
     * Create document-related activity
     */
    public function createDocumentActivity(string $driverFirebaseUid, string $activityType, $documentId, array $additionalData = []): ?DriverActivity
    {
        return $this->createActivity($driverFirebaseUid, $activityType, array_merge([
            'document_id' => $documentId,
            'related_entity_type' => 'document',
            'related_entity_id' => $documentId
        ], $additionalData));
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['activity_type'])) {
            $query->where('activity_type', $filters['activity_type']);
        }

        if (!empty($filters['activity_category'])) {
            $query->where('activity_category', $filters['activity_category']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }

        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (!empty($filters['ride_id'])) {
            $query->where('ride_id', $filters['ride_id']);
        }

        if (!empty($filters['document_id'])) {
            $query->where('document_id', $filters['document_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query;
    }

    /**
     * Prepare activity data for creation
     */
    private function prepareActivityData(string $driverFirebaseUid, string $activityType, array $activityData): array
    {
        return array_merge([
            'driver_firebase_uid' => $driverFirebaseUid,
            'activity_type' => $activityType,
            'activity_category' => DriverActivity::getCategoryForType($activityType),
            'title' => DriverActivity::getDefaultTitle($activityType),
            'description' => '',
            'priority' => DriverActivity::getPriorityForType($activityType),
            'is_read' => false,
            'status' => DriverActivity::STATUS_ACTIVE,
            'metadata' => [],
            'created_by' => 'system'
        ], $activityData);
    }

    /**
     * Get most active day for driver
     */
    private function getMostActiveDay(string $driverFirebaseUid): ?array
    {
        $activities = DriverActivity::where('driver_firebase_uid', $driverFirebaseUid)
                                   ->recent(30)
                                   ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                                   ->groupBy('date')
                                   ->orderBy('count', 'desc')
                                   ->first();

        return $activities ? [
            'date' => $activities->date,
            'count' => $activities->count
        ] : null;
    }

    /**
     * Get most active drivers
     */
    private function getMostActiveDrivers(int $limit = 10): array
    {
        return DriverActivity::recent(30)
                            ->select('driver_firebase_uid', DB::raw('count(*) as count'))
                            ->groupBy('driver_firebase_uid')
                            ->orderBy('count', 'desc')
                            ->limit($limit)
                            ->pluck('count', 'driver_firebase_uid')
                            ->toArray();
    }

    /**
     * Get empty stats array
     */
    private function getEmptyStats(): array
    {
        return [
            'total_activities' => 0,
            'activities_by_type' => [],
            'activities_by_category' => [],
            'activities_today' => 0,
            'activities_this_week' => 0,
            'activities_this_month' => 0,
            'unread_activities' => 0,
            'high_priority_activities' => 0,
            'latest_activity' => null,
            'most_active_day' => null
        ];
    }

    /**
     * Get empty system stats array
     */
    private function getEmptySystemStats(): array
    {
        return [
            'total_activities' => 0,
            'activities_by_type' => [],
            'activities_by_category' => [],
            'activities_today' => 0,
            'activities_this_week' => 0,
            'activities_this_month' => 0,
            'unread_activities' => 0,
            'high_priority_activities' => 0,
            'most_active_drivers' => []
        ];
    }
}