<?php

namespace App\Modules\Driver\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Driver\Services\DriverActivityService;

use App\Modules\Driver\Models\DriverActivity;

class AdminActivityController extends Controller
{
    protected $driverService;
    protected $DriverActivityService;

    public function __construct(DriverService $driverService, DriverActivityService $DriverActivityService)
    {
        $this->driverService = $driverService;
        $this->DriverActivityService = $DriverActivityService;
    }

    /**
     * Display activity management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'activity_type' => $request->get('activity_type'),
                'activity_category' => $request->get('activity_category'),
                'priority' => $request->get('priority'),
                'driver_firebase_uid' => $request->get('driver_firebase_uid'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'limit' => $request->get('limit', 100)
            ];

            // Get all activities from all drivers
            $allDrivers = $this->driverService->getAllDrivers(['limit' => 1000]);
            $activities = collect();
            
            foreach ($allDrivers as $driver) {
                $driverActivities = $this->driverService->getDriverActivities($driver['firebase_uid'], ['limit' => 500]);
                foreach ($driverActivities as $activity) {
                    $activity['driver_name'] = $driver['name'];
                    $activity['driver_email'] = $driver['email'];
                    $activities->push($activity);
                }
            }

            // Apply filters
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $activities = $activities->filter(function($activity) use ($search) {
                    return stripos($activity['title'] ?? '', $search) !== false ||
                           stripos($activity['description'] ?? '', $search) !== false ||
                           stripos($activity['driver_name'] ?? '', $search) !== false;
                });
            }

            if (!empty($filters['activity_type'])) {
                $activities = $activities->where('activity_type', $filters['activity_type']);
            }

            if (!empty($filters['activity_category'])) {
                $activities = $activities->where('activity_category', $filters['activity_category']);
            }

            if (!empty($filters['priority'])) {
                $activities = $activities->where('priority', $filters['priority']);
            }

            if (!empty($filters['driver_firebase_uid'])) {
                $activities = $activities->where('driver_firebase_uid', $filters['driver_firebase_uid']);
            }

            if (!empty($filters['date_from'])) {
                $dateFrom = \Carbon\Carbon::parse($filters['date_from'])->startOfDay();
                $activities = $activities->filter(function($activity) use ($dateFrom) {
                    $activityDate = \Carbon\Carbon::parse($activity['created_at'] ?? now());
                    return $activityDate->gte($dateFrom);
                });
            }

            if (!empty($filters['date_to'])) {
                $dateTo = \Carbon\Carbon::parse($filters['date_to'])->endOfDay();
                $activities = $activities->filter(function($activity) use ($dateTo) {
                    $activityDate = \Carbon\Carbon::parse($activity['created_at'] ?? now());
                    return $activityDate->lte($dateTo);
                });
            }

            // Sort by creation date descending
            $activities = $activities->sortByDesc('created_at');

            // Paginate
            $currentPage = $request->get('page', 1);
            $perPage = $filters['limit'];
            $totalActivities = $activities->count();
            $activities = $activities->forPage($currentPage, $perPage);

            Log::info('Admin activity dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters
            ]);
            
            return view('driver::admin.activities.index', compact(
                'activities', 
                'totalActivities'
            ) + $filters + [
                'activityTypes' => DriverActivity::getActivityTypes(),
                'categories' => DriverActivity::getCategories(),
                'priorities' => DriverActivity::getPriorities(),
                'drivers' => $allDrivers
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading admin activity dashboard: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading activity dashboard.');
        }
    }

    /**
     * Show detailed activity information
     */
    public function show(string $activityId)
    {
        try {
            $activity = $this->DriverActivityService->getActivityById($activityId);
            
            if (!$activity) {
                return redirect()->route('admin.activities.index')
                    ->with('error', 'Activity not found.');
            }

            // Get driver information
            $driver = $this->driverService->getDriverById($activity['driver_firebase_uid']);

            Log::info('Admin viewed activity details', [
                'admin' => session('firebase_user.email'),
                'activity_id' => $activityId
            ]);
            
            return view('driver::admin.activities.show', compact(
                'activity',
                'driver'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error loading activity details: ' . $e->getMessage());
            return redirect()->route('admin.activities.index')
                ->with('error', 'Error loading activity details.');
        }
    }

    /**
     * Show form for creating new activity
     */
    public function create(Request $request)
    {
        $driverFirebaseUid = $request->get('driver_firebase_uid');
        $driver = null;
        
        if ($driverFirebaseUid) {
            $driver = $this->driverService->getDriverById($driverFirebaseUid);
        }

        return view('driver::admin.activities.create', [
            'driver' => $driver,
            'activityTypes' => DriverActivity::getActivityTypes(),
            'categories' => DriverActivity::getCategories(),
            'priorities' => DriverActivity::getPriorities(),
            'drivers' => $this->driverService->getAllDrivers(['limit' => 1000])
        ]);
    }

    /**
     * Store newly created activity
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_firebase_uid' => 'required|string',
            'activity_type' => 'required|in:' . implode(',', array_keys(DriverActivity::getActivityTypes())),
            'activity_category' => 'required|in:' . implode(',', array_keys(DriverActivity::getCategories())),
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'priority' => 'required|in:' . implode(',', array_keys(DriverActivity::getPriorities())),
            'location_latitude' => 'nullable|numeric|between:-90,90',
            'location_longitude' => 'nullable|numeric|between:-180,180',
            'location_address' => 'nullable|string|max:255',
            'related_entity_type' => 'nullable|string|max:50',
            'related_entity_id' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $activityData = $request->all();
            $activityData['created_by'] = session('firebase_user.uid');

            $result = $this->driverService->createActivity(
                $request->driver_firebase_uid,
                $request->activity_type,
                $activityData
            );

            if ($result) {
                Log::info('Admin created activity', [
                    'admin' => session('firebase_user.email'),
                    'activity_id' => $result['id'] ?? 'unknown',
                    'driver_id' => $request->driver_firebase_uid
                ]);
                
                return redirect()->route('admin.activities.index')
                    ->with('success', 'Activity created successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to create activity.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error creating activity: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error creating activity: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing activity
     */
    public function edit(string $activityId)
    {
        try {
            $activity = $this->DriverActivityService->getActivityById($activityId);
            
            if (!$activity) {
                return redirect()->route('admin.activities.index')
                    ->with('error', 'Activity not found.');
            }

            $driver = $this->driverService->getDriverById($activity['driver_firebase_uid']);

            return view('driver::admin.activities.edit', [
                'activity' => $activity,
                'driver' => $driver,
                'activityTypes' => DriverActivity::getActivityTypes(),
                'categories' => DriverActivity::getCategories(),
                'priorities' => DriverActivity::getPriorities()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading activity for edit: ' . $e->getMessage());
            return redirect()->route('admin.activities.index')
                ->with('error', 'Error loading activity for editing.');
        }
    }

    /**
     * Update activity information
     */
    public function update(Request $request, string $activityId)
    {
        $validator = Validator::make($request->all(), [
            'activity_type' => 'required|in:' . implode(',', array_keys(DriverActivity::getActivityTypes())),
            'activity_category' => 'required|in:' . implode(',', array_keys(DriverActivity::getCategories())),
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'priority' => 'required|in:' . implode(',', array_keys(DriverActivity::getPriorities())),
            'location_latitude' => 'nullable|numeric|between:-90,90',
            'location_longitude' => 'nullable|numeric|between:-180,180',
            'location_address' => 'nullable|string|max:255',
            'related_entity_type' => 'nullable|string|max:50',
            'related_entity_id' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $activityData = $request->all();
            $activityData['updated_by'] = session('firebase_user.uid');

            $result = $this->DriverActivityService->updateActivity($activityId, $activityData);

            if ($result) {
                Log::info('Admin updated activity', [
                    'admin' => session('firebase_user.email'),
                    'activity_id' => $activityId
                ]);
                
                return redirect()->route('admin.activities.show', $activityId)
                    ->with('success', 'Activity updated successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to update activity.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating activity: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating activity: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete activity
     */
    public function destroy(string $activityId)
    {
        try {
            $activity = $this->DriverActivityService->getActivityById($activityId);
            
            if (!$activity) {
                return redirect()->route('admin.activities.index')
                    ->with('error', 'Activity not found.');
            }

            $result = $this->DriverActivityService->deleteActivity($activityId);

            if ($result) {
                Log::info('Admin deleted activity', [
                    'admin' => session('firebase_user.email'),
                    'activity_id' => $activityId
                ]);
                
                return redirect()->route('admin.activities.index')
                    ->with('success', 'Activity deleted successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to delete activity.');
            }

        } catch (\Exception $e) {
            Log::error('Error deleting activity: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting activity: ' . $e->getMessage());
        }
    }

    /**
     * Mark activity as read (AJAX)
     */
    public function markAsRead(string $activityId)
    {
        try {
            $result = $this->driverService->markActivityAsRead($activityId);

            if ($result) {
                Log::info('Admin marked activity as read', [
                    'admin' => session('firebase_user.email'),
                    'activity_id' => $activityId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Activity marked as read successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark activity as read'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error marking activity as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error marking activity as read: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Archive activity (AJAX)
     */
    public function archive(string $activityId)
    {
        try {
            $result = $this->DriverActivityService->archiveActivity($activityId);

            if ($result) {
                Log::info('Admin archived activity', [
                    'admin' => session('firebase_user.email'),
                    'activity_id' => $activityId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Activity archived successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to archive activity'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error archiving activity: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error archiving activity: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk operations on activities
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:mark_read,archive,delete',
            'activity_ids' => 'required|array|min:1',
            'activity_ids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $action = $request->action;
            $activityIds = $request->activity_ids;
            
            $processedCount = 0;
            $failedCount = 0;

            foreach ($activityIds as $activityId) {
                try {
                    $success = $this->executeBulkActivityAction($action, $activityId);
                    if ($success) {
                        $processedCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning('Bulk activity action failed', [
                        'activity_id' => $activityId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Admin performed bulk activity action', [
                'admin' => session('firebase_user.email'),
                'action' => $action,
                'processed' => $processedCount,
                'failed' => $failedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->getBulkActivityActionMessage($action, $processedCount, $failedCount),
                'processed_count' => $processedCount,
                'failed_count' => $failedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk activity action error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Activity statistics dashboard
     */
    public function statistics()
    {
        try {
            $statistics = $this->driverService->getSystemAnalytics()['activity_statistics'] ?? [];
            
            return view('driver::admin.activities.statistics', compact('statistics'));

        } catch (\Exception $e) {
            Log::error('Error loading activity statistics: ' . $e->getMessage());
            return redirect()->route('admin.activities.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    /**
     * Helper: Execute bulk activity action
     */
    private function executeBulkActivityAction(string $action, string $activityId): bool
    {
        switch ($action) {
            case 'mark_read':
                return $this->driverService->markActivityAsRead($activityId);
            case 'archive':
                return $this->DriverActivityService->archiveActivity($activityId);
            case 'delete':
                return $this->DriverActivityService->deleteActivity($activityId);
            default:
                return false;
        }
    }

    /**
     * Helper: Get bulk activity action message
     */
    private function getBulkActivityActionMessage(string $action, int $processed, int $failed): string
    {
        $actionPast = [
            'mark_read' => 'marked as read',
            'archive' => 'archived',
            'delete' => 'deleted'
        ][$action];

        $message = "Successfully {$actionPast} {$processed} activities";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }
        
        return $message . ".";
    }
}