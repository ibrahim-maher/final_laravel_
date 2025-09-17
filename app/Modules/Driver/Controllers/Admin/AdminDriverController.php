<?php

namespace App\Modules\Driver\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Driver\Services\DriverVehicleService;
use App\Modules\Driver\Services\DriverDocumentService;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\DriverLicense;
use App\Rules\FirestoreUniqueDriver;

class AdminDriverController extends Controller
{
    protected $driverService;
    protected $vehicleService;
    protected $documentService;

    public function __construct(
        DriverService $driverService,
        DriverVehicleService $vehicleService,
        DriverDocumentService $documentService
    ) {
        $this->driverService = $driverService;
        $this->vehicleService = $vehicleService;
        $this->documentService = $documentService;
    }

    // ============ DASHBOARD AND LISTING ============

    /**
     * Display driver management dashboard - OPTIMIZED
     */
    /**
 * Display driver management dashboard - COMPLETELY FIXED
 */
public function index(Request $request)
{
    try {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'verification_status' => $request->get('verification_status'),
            'availability_status' => $request->get('availability_status'),
            'limit' => min($request->get('limit', 15), 25)
        ];

        // COMPLETELY REMOVE CACHING FOR DRIVERS LIST
        // Get drivers directly without any caching
        $drivers = $this->driverService->getAllDrivers($filters);
        
        // Debug logging to see what we're getting
        Log::info('Drivers data structure', [
            'drivers_type' => gettype($drivers),
            'drivers_class' => get_class($drivers),
            'drivers_count' => method_exists($drivers, 'count') ? $drivers->count() : count($drivers),
            'first_item_type' => $drivers->count() > 0 ? gettype($drivers->first()) : 'none',
            'first_item_class' => $drivers->count() > 0 && is_object($drivers->first()) ? get_class($drivers->first()) : 'none'
        ]);
        
        // Cache total count for 5 minutes only when needed
        $totalDrivers = cache()->remember('total_drivers_count', 300, function() {
            return $this->driverService->getTotalDriversCount();
        });
        
        // Cache statistics for 5 minutes
        $statistics = cache()->remember('driver_statistics', 300, function() {
            return $this->driverService->getDriverStatistics();
        });
        
        Log::info('Admin driver dashboard accessed', [
            'admin' => session('firebase_user.email'),
            'filters' => $filters,
            'result_count' => $drivers->count(),
            'total_drivers' => $totalDrivers
        ]);
        
        return view('driver::admin.drivers.index', compact(
            'drivers', 
            'totalDrivers', 
            'statistics'
        ) + $filters);
        
    } catch (\Exception $e) {
        Log::error('Error loading admin driver dashboard: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->with('error', 'Error loading driver dashboard: ' . $e->getMessage());
    }
}

    /**
     * Show detailed driver information - HEAVILY OPTIMIZED
     */
    public function show(string $firebaseUid)
    {
        try {
            // Cache driver details for 2 minutes
            $cacheKey = "driver_details_{$firebaseUid}";
            $driverData = cache()->remember($cacheKey, 120, function() use ($firebaseUid) {
                
                $driver = $this->driverService->getDriverById($firebaseUid);
                if (!$driver) {
                    return null;
                }

                // Get only essential data initially
                return [
                    'driver' => $driver,
                    'vehicles' => $this->driverService->getDriverVehicles($firebaseUid),
                    'profileCompletion' => $this->driverService->getDriverProfileCompletion($firebaseUid)
                ];
            });

            if (!$driverData || !$driverData['driver']) {
                return redirect()->route('admin.drivers.index')
                    ->with('error', 'Driver not found.');
            }

            // Load secondary data separately with longer cache
            $secondaryData = cache()->remember("driver_secondary_{$firebaseUid}", 300, function() use ($firebaseUid) {
                return [
                    'rideStats' => $this->driverService->getDriverRideStatistics($firebaseUid),
                    'performanceMetrics' => $this->driverService->getDriverPerformanceMetrics($firebaseUid)
                ];
            });

            // Load documents and activities separately (these change more frequently)
            $dynamicData = cache()->remember("driver_dynamic_{$firebaseUid}", 60, function() use ($firebaseUid) {
                return [
                    'documents' => $this->driverService->getDriverDocuments($firebaseUid),
                    'activities' => $this->driverService->getDriverActivities($firebaseUid, ['limit' => 10]), // Reduced from 30
                ];
            });

            // Load recent rides with minimal data
            $recentRides = cache()->remember("driver_recent_rides_{$firebaseUid}", 180, function() use ($firebaseUid) {
                return $this->driverService->getDriverRides($firebaseUid, ['limit' => 5]); // Reduced from 20
            });

            // Merge all data
            $allData = array_merge($driverData, $secondaryData, $dynamicData, [
                'rides' => $recentRides
            ]);

            Log::info('Admin viewed driver details', [
                'admin' => session('firebase_user.email'),
                'driver_id' => $firebaseUid
            ]);
            
            return view('driver::admin.drivers.show', $allData);
            
        } catch (\Exception $e) {
            Log::error('Error loading driver details: ' . $e->getMessage());
            return redirect()->route('admin.drivers.index')
                ->with('error', 'Error loading driver details.');
        }
    }

    // ============ AJAX ENDPOINTS ============

    /**
     * AJAX endpoint to load driver tabs dynamically
     */
    public function loadTab(Request $request, string $firebaseUid, string $tab)
    {
        try {
            $data = [];
            
            switch ($tab) {
                case 'rides':
                    $data = cache()->remember("driver_all_rides_{$firebaseUid}", 180, function() use ($firebaseUid) {
                        return $this->driverService->getDriverRides($firebaseUid, ['limit' => 50]);
                    });
                    break;
                    
                case 'activities':
                    $data = cache()->remember("driver_all_activities_{$firebaseUid}", 120, function() use ($firebaseUid) {
                        return $this->driverService->getDriverActivities($firebaseUid, ['limit' => 50]);
                    });
                    break;
                    
                case 'documents':
                    $data = $this->driverService->getDriverDocuments($firebaseUid);
                    break;
                    
                default:
                    return response()->json(['error' => 'Invalid tab'], 400);
            }
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading driver tab: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }

    /**
     * Test cache clearing functionality
     */
    public function testCacheClearing(string $firebaseUid)
    {
        try {
            // Set a test cache value
            cache()->put("test_driver_cache_{$firebaseUid}", 'test_value', 60);
            Log::info('TEST: Set test cache value');
            
            // Verify it exists
            $exists = cache()->has("test_driver_cache_{$firebaseUid}");
            Log::info('TEST: Cache exists after set', ['exists' => $exists]);
            
            // Clear using DriverService
            $this->driverService->clearDriverCache($firebaseUid);
            
            // Check if our test cache still exists (it should since it's not in the clear list)
            $stillExists = cache()->has("test_driver_cache_{$firebaseUid}");
            Log::info('TEST: Test cache still exists after clear', ['still_exists' => $stillExists]);
            
            // Clean up
            cache()->forget("test_driver_cache_{$firebaseUid}");
            
            return response()->json([
                'test_result' => 'Cache clearing test completed',
                'cache_service_available' => true,
                'cache_exists_after_set' => $exists,
                'cache_exists_after_clear' => $stillExists
            ]);
            
        } catch (\Exception $e) {
            Log::error('TEST: Cache test failed', ['error' => $e->getMessage()]);
            return response()->json([
                'test_result' => 'Cache test failed',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update driver status (AJAX) - CACHE FIXED
     */
    public function updateStatus(Request $request, string $firebaseUid)
    {
        Log::info('AdminDriverController: updateStatus START', [
            'firebase_uid' => $firebaseUid,
            'status' => $request->status,
            'admin' => session('firebase_user.email'),
            'timestamp' => now()->toDateTimeString()
        ]);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:activate,deactivate,suspend,verify,active,inactive,suspended,pending'
        ]);

        if ($validator->fails()) {
            Log::warning('AdminDriverController: Validation failed', [
                'firebase_uid' => $firebaseUid,
                'errors' => $validator->errors()->all()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Invalid status: ' . implode(', ', $validator->errors()->all())
            ], 400);
        }

        try {
            // Map action to actual status
            $statusMap = [
                'activate' => 'active',
                'deactivate' => 'inactive', 
                'suspend' => 'suspended',
                'verify' => 'active',
            ];
            
            $actualStatus = $statusMap[$request->status] ?? $request->status;
            
            Log::info('AdminDriverController: Calling updateDriverStatus', [
                'firebase_uid' => $firebaseUid,
                'actual_status' => $actualStatus
            ]);
            
            // This will automatically clear cache via DriverService
            $result = $this->driverService->updateDriverStatus($firebaseUid, $actualStatus);
            
            Log::info('AdminDriverController: updateDriverStatus result', [
                'firebase_uid' => $firebaseUid,
                'result' => $result ? 'SUCCESS' : 'FAILED'
            ]);
            
            // Handle verification separately if needed
            if ($request->status === 'verify') {
                Log::info('AdminDriverController: Updating verification status', [
                    'firebase_uid' => $firebaseUid
                ]);
                
                $verificationResult = $this->driverService->updateDriverVerificationStatus(
                    $firebaseUid, 
                    'verified', 
                    session('firebase_user.uid')
                );
                
                Log::info('AdminDriverController: Verification update result', [
                    'firebase_uid' => $firebaseUid,
                    'verification_result' => $verificationResult ? 'SUCCESS' : 'FAILED'
                ]);
            }

            if ($result) {
                Log::info('AdminDriverController: Status update completed successfully', [
                    'firebase_uid' => $firebaseUid,
                    'final_status' => $actualStatus
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Driver status updated successfully!',
                    'new_status' => $actualStatus,
                    'timestamp' => now()->toDateTimeString()
                ]);
            } else {
                Log::error('AdminDriverController: Failed to update driver status', [
                    'firebase_uid' => $firebaseUid,
                    'requested_status' => $actualStatus
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update driver status'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('AdminDriverController: Exception in updateStatus', [
                'firebase_uid' => $firebaseUid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update verification status (AJAX)
     */
    public function updateVerificationStatus(Request $request, string $firebaseUid)
    {
        $validator = Validator::make($request->all(), [
            'verification_status' => 'required|in:pending,verified,rejected',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid verification status'
            ], 400);
        }

        try {
            // This will automatically clear cache via DriverService
            $result = $this->driverService->updateDriverVerificationStatus(
                $firebaseUid, 
                $request->verification_status,
                session('firebase_user.uid'),
                $request->notes
            );

            if ($result) {
                Log::info('Admin updated driver verification status', [
                    'admin' => session('firebase_user.email'),
                    'driver_id' => $firebaseUid,
                    'verification_status' => $request->verification_status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Verification status updated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update verification status'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating verification status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating verification: ' . $e->getMessage()
            ]);
        }
    }

    // ============ CRUD OPERATIONS ============

    /**
     * Show form for creating new driver
     */
    public function create()
    {
        return view('driver::admin.drivers.create', [
            'statuses' => Driver::getStatuses(),
            'verificationStatuses' => Driver::getVerificationStatuses(),
            'availabilityStatuses' => Driver::getAvailabilityStatuses(),
            'vehicleTypes' => Vehicle::getVehicleTypes(),
            'fuelTypes' => Vehicle::getFuelTypes(),
            'licenseClasses' => DriverLicense::getLicenseClasses(),
            'licenseTypes' => DriverLicense::getLicenseTypes(),
            'documentTypes' => DriverDocument::getDocumentTypes()
        ]);
    }

    /**
     * Store newly created driver with vehicle, license and documents
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Driver validation rules
            'firebase_uid' => ['required', 'string', 'max:255', new FirestoreUniqueDriver($this->driverService)],
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:drivers,email', // Add unique validation
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:18 years ago',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date|after:today',
            'license_class' => 'nullable|string|max:20',
            'license_type' => 'nullable|string|max:20',
            'issuing_state' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,suspended,pending',
            'verification_status' => 'required|in:pending,verified,rejected',
            'availability_status' => 'required|in:available,busy,offline',
            
            // Vehicle validation rules
            'vehicle_make' => 'nullable|string|max:100',
            'vehicle_model' => 'nullable|string|max:100',
            'vehicle_year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'vehicle_color' => 'nullable|string|max:50',
            'vehicle_license_plate' => 'nullable|string|max:20',
            'vehicle_vin' => 'nullable|string|max:17',
            'vehicle_type' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'vehicle_seats' => 'nullable|integer|min:2|max:50',
            'registration_number' => 'nullable|string|max:50',
            'registration_expiry' => 'nullable|date|after:today',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_policy_number' => 'nullable|string|max:50',
            'insurance_expiry' => 'nullable|date|after:today',
            
            // Document validation rules
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'license_front' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:5120',
            'license_back' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:5120',
            'vehicle_registration_doc' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'insurance_certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'vehicle_photos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Prepare driver data
            $driverData = $this->prepareDriverData($request);
            
            // Create driver (this will automatically clear cache)
            $driver = $this->driverService->createDriver($driverData);
            
            if (!$driver) {
                return redirect()->back()
                    ->with('error', 'Failed to create driver.')
                    ->withInput();
            }

            $firebaseUid = $request->firebase_uid;

            // Create vehicle if vehicle data provided
            if ($this->hasVehicleData($request)) {
                $vehicleData = $this->prepareVehicleData($request, $firebaseUid);
                $vehicle = $this->vehicleService->createVehicle($vehicleData);
                
                if ($vehicle) {
                    Log::info('Vehicle created for driver', [
                        'driver_id' => $firebaseUid,
                        'vehicle_id' => $vehicle['id'] ?? 'unknown'
                    ]);
                }
            }

            // Upload documents if provided
            $this->uploadDriverDocuments($request, $firebaseUid);

            Log::info('Admin created driver with additional data', [
                'admin' => session('firebase_user.email'),
                'driver_id' => $firebaseUid,
                'has_vehicle' => $this->hasVehicleData($request),
                'documents_uploaded' => $this->countUploadedFiles($request)
            ]);

            return redirect()->route('admin.drivers.show', $firebaseUid)
                ->with('success', 'Driver created successfully with vehicle and documents!');

        } catch (\Exception $e) {
            Log::error('Error creating driver with additional data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Error creating driver: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing driver
     */
    public function edit(string $firebaseUid)
    {
        try {
            $driver = $this->driverService->getDriverById($firebaseUid);
            
            if (!$driver) {
                return redirect()->route('admin.drivers.index')
                    ->with('error', 'Driver not found.');
            }

            // Get additional data for edit form
            $vehicles = $this->vehicleService->getDriverVehicles($firebaseUid);
            $documents = $this->documentService->getDriverDocuments($firebaseUid);

            return view('driver::admin.drivers.edit', [
                'driver' => $driver,
                'vehicles' => $vehicles,
                'documents' => $documents,
                'statuses' => Driver::getStatuses(),
                'verificationStatuses' => Driver::getVerificationStatuses(),
                'availabilityStatuses' => Driver::getAvailabilityStatuses(),
                'vehicleTypes' => Vehicle::getVehicleTypes(),
                'fuelTypes' => Vehicle::getFuelTypes(),
                'licenseClasses' => DriverLicense::getLicenseClasses(),
                'licenseTypes' => DriverLicense::getLicenseTypes(),
                'documentTypes' => DriverDocument::getDocumentTypes()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading driver for edit: ' . $e->getMessage());
            return redirect()->route('admin.drivers.index')
                ->with('error', 'Error loading driver for editing.');
        }
    }

    /**
     * Update driver information
     */
    public function update(Request $request, string $firebaseUid)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:18 years ago',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date|after:today',
            'status' => 'required|in:active,inactive,suspended,pending',
            'verification_status' => 'required|in:pending,verified,rejected',
            'availability_status' => 'required|in:available,busy,offline',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $driverData = $request->all();
            $driverData['updated_by'] = session('firebase_user.uid');

            // This will automatically clear cache
            $result = $this->driverService->updateDriver($firebaseUid, $driverData);

            if ($result) {
                Log::info('Admin updated driver', [
                    'admin' => session('firebase_user.email'),
                    'driver_id' => $firebaseUid
                ]);
                
                return redirect()->route('admin.drivers.show', $firebaseUid)
                    ->with('success', 'Driver updated successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to update driver.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating driver: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating driver: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete driver
     */
    public function destroy(string $firebaseUid)
    {
        try {
            $driver = $this->driverService->getDriverById($firebaseUid);
            
            if (!$driver) {
                return redirect()->route('admin.drivers.index')
                    ->with('error', 'Driver not found.');
            }

            // This will automatically clear cache
            $result = $this->driverService->deleteDriver($firebaseUid);

            if ($result) {
                Log::info('Admin deleted driver', [
                    'admin' => session('firebase_user.email'),
                    'driver_id' => $firebaseUid
                ]);
                
                return redirect()->route('admin.drivers.index')
                    ->with('success', 'Driver deleted successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to delete driver.');
            }

        } catch (\Exception $e) {
            Log::error('Error deleting driver: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting driver: ' . $e->getMessage());
        }
    }

    // ============ DRIVER ACTIONS ============

    /**
     * Activate driver account
     */
    public function activate(string $firebaseUid)
    {
        try {
            $result = $this->driverService->activateDriver(
                $firebaseUid, 
                session('firebase_user.uid')
            );

            if ($result['success']) {
                Log::info('Admin activated driver', [
                    'admin' => session('firebase_user.email'),
                    'driver_id' => $firebaseUid
                ]);

                return redirect()->route('admin.drivers.show', $firebaseUid)
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->with('error', $result['message'])
                    ->with('completion_status', $result['completion_status'] ?? null);
            }

        } catch (\Exception $e) {
            Log::error('Error activating driver: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error activating driver: ' . $e->getMessage());
        }
    }

    // ============ BULK OPERATIONS ============

    /**
     * Bulk operations on drivers
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,suspend,verify,delete',
            'driver_ids' => 'required|array|min:1',
            'driver_ids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->driverService->performBulkAction(
                $request->action, 
                $request->driver_ids
            );

            if ($result['success']) {
                Log::info('Admin performed bulk action', [
                    'admin' => session('firebase_user.email'),
                    'action' => $request->action,
                    'count' => count($request->driver_ids)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $this->getBulkActionMessage(
                        $request->action, 
                        $result['processed_count'], 
                        $result['failed_count']
                    ),
                    'processed_count' => $result['processed_count'],
                    'failed_count' => $result['failed_count']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Bulk action error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ]);
        }
    }

    // ============ IMPORT/EXPORT ============

    /**
     * Export drivers data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel',
            'status' => 'nullable|in:active,inactive,suspended,pending',
            'verification_status' => 'nullable|in:pending,verified,rejected',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date|after_or_equal:created_from'
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.drivers.index')
                ->with('error', 'Invalid export parameters.');
        }

        try {
            $filters = [
                'status' => $request->status,
                'verification_status' => $request->verification_status,
                'created_from' => $request->created_from,
                'created_to' => $request->created_to
            ];

            $drivers = $this->driverService->exportDrivers($filters);
            
            $filename = 'drivers_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
            
            Log::info('Admin exported drivers', [
                'admin' => session('firebase_user.email'),
                'count' => count($drivers),
                'filters' => $filters
            ]);

            return $this->generateCsvExport($drivers, $filename);

        } catch (\Exception $e) {
            Log::error('Error exporting drivers: ' . $e->getMessage());
            return redirect()->route('admin.drivers.index')
                ->with('error', 'Error exporting drivers: ' . $e->getMessage());
        }
    }

    /**
     * Import drivers from file
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $file = $request->file('file');
            $driversData = $this->processCsvFile($file->getRealPath());

            $result = $this->driverService->bulkImportDrivers($driversData);

            if ($result['success']) {
                Log::info('Admin imported drivers', [
                    'admin' => session('firebase_user.email'),
                    'imported_count' => $result['imported_count']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$result['imported_count']} drivers",
                    'imported_count' => $result['imported_count'],
                    'failed_count' => $result['failed_count']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error importing drivers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error importing drivers: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sync drivers from Firebase
     */
    public function syncFirebase()
    {
        try {
            $result = $this->driverService->syncFirebaseDrivers();

            if ($result['success']) {
                Log::info('Admin synced Firebase drivers', [
                    'admin' => session('firebase_user.email'),
                    'synced_count' => $result['synced_count']
                ]);

                return redirect()->route('admin.drivers.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->route('admin.drivers.index')
                    ->with('error', 'Failed to sync drivers: ' . $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Error syncing Firebase drivers: ' . $e->getMessage());
            return redirect()->route('admin.drivers.index')
                ->with('error', 'Error syncing drivers: ' . $e->getMessage());
        }
    }

    // ============ STATISTICS ============

    /**
     * Driver statistics dashboard
     */
    public function statistics()
    {
        try {
            $statistics = $this->driverService->getDriverStatistics();
            $systemAnalytics = $this->driverService->getSystemAnalytics();
            
            return view('driver::admin.drivers.statistics', compact(
                'statistics', 
                'systemAnalytics'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading driver statistics: ' . $e->getMessage());
            return redirect()->route('admin.drivers.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    // ============ HELPER METHODS ============

    /**
     * Prepare driver data from request
     */
    private function prepareDriverData(Request $request): array
    {
        return [
            'firebase_uid' => $request->firebase_uid,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'license_number' => $request->license_number,
            'license_expiry' => $request->license_expiry,
            'license_class' => $request->license_class,
            'license_type' => $request->license_type,
            'issuing_state' => $request->issuing_state,
            'status' => $request->status,
            'verification_status' => $request->verification_status,
            'availability_status' => $request->availability_status,
            'created_by' => session('firebase_user.uid')
        ];
    }

    /**
     * Check if request has vehicle data
     */
    private function hasVehicleData(Request $request): bool
    {
        return $request->filled('vehicle_make') && 
               $request->filled('vehicle_model') && 
               $request->filled('vehicle_year');
    }

    /**
     * Prepare vehicle data from request
     */
    private function prepareVehicleData(Request $request, string $firebaseUid): array
    {
        return [
            'driver_firebase_uid' => $firebaseUid,
            'make' => $request->vehicle_make,
            'model' => $request->vehicle_model,
            'year' => $request->vehicle_year,
            'color' => $request->vehicle_color,
            'license_plate' => $request->vehicle_license_plate,
            'vin' => $request->vehicle_vin,
            'vehicle_type' => $request->vehicle_type,
            'fuel_type' => $request->fuel_type,
            'seats' => $request->vehicle_seats ?? 4,
            'registration_number' => $request->registration_number,
            'registration_expiry' => $request->registration_expiry,
            'insurance_provider' => $request->insurance_provider,
            'insurance_policy_number' => $request->insurance_policy_number,
            'insurance_expiry' => $request->insurance_expiry,
            'is_primary' => true,
            'status' => Vehicle::STATUS_ACTIVE,
            'verification_status' => Vehicle::VERIFICATION_PENDING
        ];
    }

    /**
     * Upload driver documents
     */
    private function uploadDriverDocuments(Request $request, string $firebaseUid): void
    {
        $documentTypes = [
            'profile_photo' => DriverDocument::TYPE_PROFILE_PHOTO,
            'license_front' => DriverDocument::TYPE_DRIVERS_LICENSE,
            'license_back' => DriverDocument::TYPE_DRIVERS_LICENSE,
            'vehicle_registration_doc' => DriverDocument::TYPE_VEHICLE_REGISTRATION,
            'insurance_certificate' => DriverDocument::TYPE_INSURANCE_CERTIFICATE
        ];

        foreach ($documentTypes as $inputName => $documentType) {
            if ($request->hasFile($inputName)) {
                try {
                    $file = $request->file($inputName);
                    $documentData = [
                        'document_type' => $documentType,
                        'document_name' => $this->getDocumentName($inputName, $file->getClientOriginalName())
                    ];

                    $result = $this->documentService->uploadDocument($firebaseUid, $file, $documentData);
                    
                    if ($result) {
                        Log::info('Document uploaded for new driver', [
                            'driver_id' => $firebaseUid,
                            'document_type' => $documentType,
                            'file_name' => $file->getClientOriginalName()
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to upload document for new driver', [
                        'driver_id' => $firebaseUid,
                        'document_type' => $documentType,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Get document name based on input type
     */
    private function getDocumentName(string $inputName, string $originalName): string
    {
        $nameMap = [
            'profile_photo' => 'Profile Photo',
            'license_front' => 'Driver License (Front)',
            'license_back' => 'Driver License (Back)',
            'vehicle_registration_doc' => 'Vehicle Registration',
            'insurance_certificate' => 'Insurance Certificate'
        ];

        return $nameMap[$inputName] ?? $originalName;
    }

    /**
     * Count uploaded files in request
     */
    private function countUploadedFiles(Request $request): int
    {
        $count = 0;
        $fileFields = ['profile_photo', 'license_front', 'license_back', 'vehicle_registration_doc', 'insurance_certificate'];
        
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $count++;
            }
        }
        
        if ($request->hasFile('vehicle_photos')) {
            $count += count($request->file('vehicle_photos'));
        }
        
        return $count;
    }

    /**
     * Generate bulk action message
     */
    private function getBulkActionMessage(string $action, int $processed, int $failed): string
    {
        $messages = [
            'activate' => "Activated {$processed} drivers",
            'deactivate' => "Deactivated {$processed} drivers",
            'suspend' => "Suspended {$processed} drivers",
            'verify' => "Verified {$processed} drivers",
            'delete' => "Deleted {$processed} drivers"
        ];

        $message = $messages[$action] ?? "Processed {$processed} drivers";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        return $message;
    }

    /**
     * Generate CSV export
     */
    private function generateCsvExport(array $drivers, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($drivers) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Firebase UID', 'Name', 'Email', 'Phone', 'Status', 
                'Verification Status', 'Join Date', 'City', 'State', 
                'License Number', 'Total Rides', 'Rating'
            ]);

            // CSV Data
            foreach ($drivers as $driver) {
                fputcsv($file, [
                    $driver['firebase_uid'] ?? '',
                    $driver['name'] ?? '',
                    $driver['email'] ?? '',
                    $driver['phone'] ?? '',
                    $driver['status'] ?? '',
                    $driver['verification_status'] ?? '',
                    $driver['join_date'] ?? '',
                    $driver['city'] ?? '',
                    $driver['state'] ?? '',
                    $driver['license_number'] ?? '',
                    $driver['total_rides'] ?? 0,
                    $driver['rating'] ?? 0
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process CSV file for import
     */
    private function processCsvFile(string $filePath): array
    {
        $drivers = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= count($header)) {
                    $driver = array_combine($header, $row);
                    $drivers[] = $driver;
                }
            }
            
            fclose($handle);
        }
        
        return $drivers;
    }
}