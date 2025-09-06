<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\DriverActivity;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DriverActivityService extends BaseService
{
    protected $collection = 'driver_activities';

    /**
     * Get driver activities
     */
    public function getDriverActivities(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('Getting activities for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $allActivities = $this->getAllDocuments(['limit' => $filters['limit'] ?? 1000]);
            
            $driverActivities = $this->filterByField($allActivities, 'driver_firebase_uid', $driverFirebaseUid);
            
            $filteredActivities = $this->applyFilters($driverActivities, $filters);
            
            // Sort by created_at desc
            return $this->sortDocuments($filteredActivities, 'created_at', 'desc');
        } catch (\Exception $e) {
            Log::error('Error getting driver activities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create activity for driver
     */
    public function createActivity(string $driverFirebaseUid, string $activityType, array $activityData = []): ?array
    {
        try {
            Log::info('Creating activity for driver', [
                'firebase_uid' => $driverFirebaseUid,
                'activity_type' => $activityType
            ]);
            
            $activityRecord = $this->prepareActivityData($driverFirebaseUid, $activityType, $activityData);
            
            return $this->createDocument($activityRecord);
        } catch (\Exception $e) {
            Log::error('Error creating activity: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get activity by ID
     */
    public function getActivityById(string $activityId): ?array
    {
        return $this->getDocumentById($activityId);
    }

    /**
     * Update activity
     */
    public function updateActivity(string $activityId, array $activityData): bool
    {
        try {
            Log::info('Updating activity', ['activity_id' => $activityId]);
            
            return $this->updateDocument($activityId, $activityData);
        } catch (\Exception $e) {
            Log::error('Error updating activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete activity
     */
    public function deleteActivity(string $activityId): bool
    {
        try {
            Log::info('Deleting activity', ['activity_id' => $activityId]);
            
            return $this->deleteDocument($activityId);
        } catch (\Exception $e) {
            Log::error('Error deleting activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Archive activity
     */
    public function archiveActivity(string $activityId): bool
    {
        try {
            return $this->updateDocument($activityId, [
                'status' => 'archived',
                'archived_at' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error archiving activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get activities by type
     */
    public function getActivitiesByType(string $activityType, array $filters = []): array
    {
        return $this->getAllDocuments(array_merge($filters, ['type' => $activityType]));
    }

    /**
     * Get activities by category
     */
    public function getActivitiesByCategory(string $category, array $filters = []): array
    {
        return $this->getAllDocuments(array_merge($filters, ['category' => $category]));
    }

    /**
     * Get recent activities for driver
     */
    public function getRecentActivitiesForDriver(string $driverFirebaseUid, int $days = 7, int $limit = 50): array
    {
        $dateFrom = now()->subDays($days)->format('Y-m-d');
        
        return $this->getDriverActivities($driverFirebaseUid, [
            'date_from' => $dateFrom,
            'limit' => $limit
        ]);
    }

    /**
     * Get activities in date range
     */
    public function getActivitiesInDateRange(string $dateFrom, string $dateTo, array $filters = []): array
    {
        return $this->getAllDocuments(array_merge($filters, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]));
    }

    /**
     * Get activity statistics for driver
     */
    public function getDriverActivityStatistics(string $driverFirebaseUid): array
    {
        try {
            $activities = $this->getDriverActivities($driverFirebaseUid, ['limit' => 10000]);
            
            $stats = [
                'total_activities' => count($activities),
                'activities_by_type' => [],
                'activities_by_category' => [],
                'activities_today' => 0,
                'activities_this_week' => 0,
                'activities_this_month' => 0,
                'latest_activity' => null,
                'most_active_day' => null
            ];
            
            $today = now()->startOfDay();
            $weekStart = now()->startOfWeek();
            $monthStart = now()->startOfMonth();
            $dailyCount = [];
            
            foreach ($activities as $activity) {
                $activityDate = Carbon::parse($activity['created_at'] ?? now());
                
                // Count by type
                $type = $activity['activity_type'] ?? 'unknown';
                $stats['activities_by_type'][$type] = ($stats['activities_by_type'][$type] ?? 0) + 1;
                
                // Count by category
                $category = $activity['activity_category'] ?? 'general';
                $stats['activities_by_category'][$category] = ($stats['activities_by_category'][$category] ?? 0) + 1;
                
                // Date-based counts
                if ($activityDate->gte($today)) {
                    $stats['activities_today']++;
                }
                
                if ($activityDate->gte($weekStart)) {
                    $stats['activities_this_week']++;
                }
                
                if ($activityDate->gte($monthStart)) {
                    $stats['activities_this_month']++;
                }
                
                // Track daily activity for most active day
                $dayKey = $activityDate->format('Y-m-d');
                $dailyCount[$dayKey] = ($dailyCount[$dayKey] ?? 0) + 1;
                
                // Latest activity
                if (!$stats['latest_activity'] || $activityDate->gt(Carbon::parse($stats['latest_activity']['created_at']))) {
                    $stats['latest_activity'] = $activity;
                }
            }
            
            // Find most active day
            if (!empty($dailyCount)) {
                $maxDay = array_keys($dailyCount, max($dailyCount))[0];
                $stats['most_active_day'] = [
                    'date' => $maxDay,
                    'count' => $dailyCount[$maxDay]
                ];
            }
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting driver activity statistics: ' . $e->getMessage());
            return [
                'total_activities' => 0,
                'activities_by_type' => [],
                'activities_by_category' => [],
                'activities_today' => 0,
                'activities_this_week' => 0,
                'activities_this_month' => 0,
                'latest_activity' => null,
                'most_active_day' => null
            ];
        }
    }

    /**
     * Get system-wide activity statistics
     */
    public function getSystemActivityStatistics(): array
    {
        try {
            $activities = $this->getAllDocuments(['limit' => 10000]);
            
            $stats = [
                'total_activities' => count($activities),
                'activities_by_type' => [],
                'activities_by_category' => [],
                'activities_today' => 0,
                'activities_this_week' => 0,
                'activities_this_month' => 0,
                'most_active_drivers' => []
            ];
            
            $today = now()->startOfDay();
            $weekStart = now()->startOfWeek();
            $monthStart = now()->startOfMonth();
            $driverActivityCount = [];
            
            foreach ($activities as $activity) {
                $activityDate = Carbon::parse($activity['created_at'] ?? now());
                
                // Count by type
                $type = $activity['activity_type'] ?? 'unknown';
                $stats['activities_by_type'][$type] = ($stats['activities_by_type'][$type] ?? 0) + 1;
                
                // Count by category
                $category = $activity['activity_category'] ?? 'general';
                $stats['activities_by_category'][$category] = ($stats['activities_by_category'][$category] ?? 0) + 1;
                
                // Date-based counts
                if ($activityDate->gte($today)) {
                    $stats['activities_today']++;
                }
                
                if ($activityDate->gte($weekStart)) {
                    $stats['activities_this_week']++;
                }
                
                if ($activityDate->gte($monthStart)) {
                    $stats['activities_this_month']++;
                }
                
                // Track driver activity count
                $driverUid = $activity['driver_firebase_uid'] ?? 'unknown';
                $driverActivityCount[$driverUid] = ($driverActivityCount[$driverUid] ?? 0) + 1;
            }
            
            // Get most active drivers (top 10)
            arsort($driverActivityCount);
            $stats['most_active_drivers'] = array_slice($driverActivityCount, 0, 10, true);
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting system activity statistics: ' . $e->getMessage());
            return [
                'total_activities' => 0,
                'activities_by_type' => [],
                'activities_by_category' => [],
                'activities_today' => 0,
                'activities_this_week' => 0,
                'activities_this_month' => 0,
                'most_active_drivers' => []
            ];
        }
    }

    /**
     * Mark activity as read
     */
    public function markActivityAsRead(string $activityId): bool
    {
        return $this->updateDocument($activityId, [
            'is_read' => true,
            'read_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Mark multiple activities as read
     */
    public function markActivitiesAsRead(array $activityIds): array
    {
        $results = [];
        
        foreach ($activityIds as $activityId) {
            $results[$activityId] = $this->markActivityAsRead($activityId);
        }
        
        return $results;
    }

    /**
     * Get unread activities for driver
     */
    public function getUnreadActivitiesForDriver(string $driverFirebaseUid): array
    {
        $activities = $this->getDriverActivities($driverFirebaseUid, ['limit' => 1000]);
        
        return array_filter($activities, function($activity) {
            return !isset($activity['is_read']) || !$activity['is_read'];
        });
    }

    /**
     * Get unread activity count for driver
     */
    public function getUnreadActivityCount(string $driverFirebaseUid): int
    {
        return count($this->getUnreadActivitiesForDriver($driverFirebaseUid));
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
            'title' => 'Activity',
            'description' => '',
            'priority' => DriverActivity::PRIORITY_NORMAL,
            'is_read' => false,
            'status' => 'active',
            'metadata' => []
        ], $activityData);
    }

    /**
     * Check if document matches search query
     */
    protected function matchesSearch(array $document, string $search): bool
    {
        $searchableFields = ['title', 'description', 'activity_type', 'activity_category'];
        
        foreach ($searchableFields as $field) {
            if (isset($document[$field]) && stripos($document[$field], $search) !== false) {
                return true;
            }
        }
        
        return false;
    }
}