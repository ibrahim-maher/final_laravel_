<?php

namespace App\Modules\Driver\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\DriverLicense;

class DriverController extends Controller
{
    protected $driverService;

    public function __construct(DriverService $driverService)
    {
        $this->driverService = $driverService;
    }

    // ============ DRIVER MANAGEMENT ============

    /**
     * Display a listing of drivers
     */
    public function index(Request $request)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        Log::info('Driver index accessed by: ' . session('firebase_user.email'));

        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'verification_status' => $request->get('verification_status'),
                'availability_status' => $request->get('availability_status'),
                'limit' => $request->get('limit', 50)
            ];

            $drivers = collect($this->driverService->getAllDrivers($filters));
            $totalDrivers = $this->driverService->getTotalDriversCount();
            
            Log::info('Retrieved drivers list', ['count' => $drivers->count(), 'total' => $totalDrivers]);
            
        } catch (\Exception $e) {
            Log::error('Error getting drivers list: ' . $e->getMessage());
            $drivers = collect([]);
            $totalDrivers = 0;
        }

        return view('driver::index', compact('drivers', 'totalDrivers') + $filters);
    }

    /**
     * Show the form for creating a new driver
     */
    public function create()
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        Log::info('Driver create form accessed by: ' . session('firebase_user.email'));
        
        return view('driver::create');
    }

    /**
     * Store a newly created driver
     */
    public function store(Request $request)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'firebase_uid' => 'required|string|max:255|unique:drivers,firebase_uid',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:drivers,email',
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
            $driverData['created_by'] = session('firebase_user.uid');

            $result = $this->driverService->createDriver($driverData);

            if ($result) {
                Log::info('Driver created successfully', ['firebase_uid' => $request->firebase_uid]);
                return redirect()->route('driver.index')->with('success', 'Driver created successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to create driver.')->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error creating driver: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error creating driver: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified driver
     */
    public function show(string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            $driver = $this->driverService->getDriverById($firebaseUid);
            
            if (!$driver) {
                return redirect()->route('driver.index')->with('error', 'Driver not found.');
            }

            // Get related data
            $vehicles = $this->driverService->getDriverVehicles($firebaseUid);
            $rides = $this->driverService->getDriverRides($firebaseUid, ['limit' => 10]);
            $documents = $this->driverService->getDriverDocuments($firebaseUid);
            $licenses = $this->driverService->getDriverLicenses($firebaseUid);
            $activities = $this->driverService->getDriverActivities($firebaseUid, ['limit' => 20]);
            $rideStats = $this->driverService->getDriverRideStatistics($firebaseUid);

            Log::info('Driver details viewed', ['firebase_uid' => $firebaseUid]);
            
            return view('driver::show', compact(
                'driver', 
                'vehicles', 
                'rides', 
                'documents', 
                'licenses', 
                'activities',
                'rideStats'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error getting driver details: ' . $e->getMessage());
            return redirect()->route('driver.index')->with('error', 'Error loading driver details.');
        }
    }

    /**
     * Show the form for editing the specified driver
     */
    public function edit(string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            $driver = $this->driverService->getDriverById($firebaseUid);
            
            if (!$driver) {
                return redirect()->route('driver.index')->with('error', 'Driver not found.');
            }

            return view('driver::edit', compact('driver'));
            
        } catch (\Exception $e) {
            Log::error('Error getting driver for edit: ' . $e->getMessage());
            return redirect()->route('driver.index')->with('error', 'Error loading driver for editing.');
        }
    }

    /**
     * Update the specified driver
     */
    public function update(Request $request, string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:drivers,email,' . $firebaseUid . ',firebase_uid',
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

            $result = $this->driverService->updateDriver($firebaseUid, $driverData);

            if ($result) {
                Log::info('Driver updated successfully', ['firebase_uid' => $firebaseUid]);
                return redirect()->route('driver.show', $firebaseUid)->with('success', 'Driver updated successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to update driver.')->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating driver: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating driver: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified driver
     */
    public function destroy(string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            $result = $this->driverService->deleteDriver($firebaseUid);

            if ($result) {
                Log::info('Driver deleted successfully', ['firebase_uid' => $firebaseUid]);
                return redirect()->route('driver.index')->with('success', 'Driver deleted successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to delete driver.');
            }

        } catch (\Exception $e) {
            Log::error('Error deleting driver: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting driver: ' . $e->getMessage());
        }
    }

    // ============ STATUS MANAGEMENT ============

    /**
     * Toggle driver status
     */
    public function toggleStatus(Request $request, string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,suspend,verify,reject'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }

        try {
            $action = $request->action;
            $result = false;

            switch ($action) {
                case 'activate':
                    $result = $this->driverService->updateDriverStatus($firebaseUid, Driver::STATUS_ACTIVE);
                    break;
                case 'deactivate':
                    $result = $this->driverService->updateDriverStatus($firebaseUid, Driver::STATUS_INACTIVE);
                    break;
                case 'suspend':
                    $result = $this->driverService->updateDriverStatus($firebaseUid, Driver::STATUS_SUSPENDED);
                    break;
                case 'verify':
                    $result = $this->driverService->updateDriverVerificationStatus(
                        $firebaseUid, 
                        Driver::VERIFICATION_VERIFIED, 
                        session('firebase_user.uid')
                    );
                    break;
                case 'reject':
                    $result = $this->driverService->updateDriverVerificationStatus(
                        $firebaseUid, 
                        Driver::VERIFICATION_REJECTED, 
                        session('firebase_user.uid')
                    );
                    break;
            }

            if ($result) {
                Log::info('Driver status toggled', ['firebase_uid' => $firebaseUid, 'action' => $action]);
                return redirect()->route('driver.index')->with('success', ucfirst($action) . ' completed successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to ' . $action . ' driver.');
            }

        } catch (\Exception $e) {
            Log::error('Error toggling driver status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error processing action: ' . $e->getMessage());
        }
    }

    // ============ BULK OPERATIONS ============

    /**
     * Perform bulk actions on selected drivers
     */
    public function bulkAction(Request $request)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,suspend,verify,delete',
            'driver_ids' => 'required|array|min:1',
            'driver_ids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input'], 400);
        }

        try {
            $action = $request->action;
            $driverIds = $request->driver_ids;
            
            Log::info('Bulk action initiated', [
                'action' => $action,
                'driver_count' => count($driverIds),
                'initiated_by' => session('firebase_user.email')
            ]);

            $result = $this->driverService->performBulkAction($action, $driverIds);

            if ($result['success']) {
                $message = $this->getBulkActionMessage($action, $result['processed_count'], $result['failed_count']);
                
                Log::info('Bulk action completed', [
                    'action' => $action,
                    'processed' => $result['processed_count'],
                    'failed' => $result['failed_count']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $message,
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
            Log::error('Bulk action exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ]);
        }
    }

    // ============ VEHICLE MANAGEMENT ============

    /**
     * Get driver's vehicles
     */
    public function vehicles(string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $vehicles = $this->driverService->getDriverVehicles($firebaseUid);
            
            return response()->json([
                'success' => true,
                'vehicles' => $vehicles
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting driver vehicles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading vehicles: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Add vehicle to driver
     */
    public function addVehicle(Request $request, string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|between:1900,' . (date('Y') + 1),
            'color' => 'required|string|max:50',
            'license_plate' => 'required|string|max:20',
            'vehicle_type' => 'required|in:sedan,suv,hatchback,pickup,van,motorcycle,bicycle,scooter',
            'fuel_type' => 'nullable|in:gasoline,diesel,electric,hybrid,cng,lpg',
            'doors' => 'nullable|integer|between:2,6',
            'seats' => 'required|integer|between:1,20',
            'is_primary' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $vehicleData = $request->all();
            $vehicleData['driver_firebase_uid'] = $firebaseUid;

            $result = $this->driverService->createVehicle($vehicleData);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle added successfully',
                    'vehicle' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add vehicle'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error adding vehicle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding vehicle: ' . $e->getMessage()
            ]);
        }
    }

    // ============ DOCUMENT MANAGEMENT ============

    /**
     * Get driver's documents
     */
    public function documents(string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $documents = $this->driverService->getDriverDocuments($firebaseUid);
            
            return response()->json([
                'success' => true,
                'documents' => $documents
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting driver documents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading documents: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Upload document for driver
     */
    public function uploadDocument(Request $request, string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:' . implode(',', array_keys(DriverDocument::getDocumentTypes())),
            'document_name' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:today',
            'issuing_authority' => 'nullable|string|max:255',
            'issuing_country' => 'nullable|string|max:100',
            'issuing_state' => 'nullable|string|max:100',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120' // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $result = $this->driverService->uploadDocument(
                $firebaseUid, 
                $request->file('file'),
                $request->only([
                    'document_type',
                    'document_name', 
                    'document_number',
                    'issue_date',
                    'expiry_date',
                    'issuing_authority',
                    'issuing_country',
                    'issuing_state'
                ])
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully',
                    'document' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload document'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Verify document
     */
    public function verifyDocument(Request $request, string $documentId)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $result = $this->driverService->verifyDocument(
                $documentId,
                session('firebase_user.uid'),
                $request->verification_notes
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document verified successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify document'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error verifying document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error verifying document: ' . $e->getMessage()
            ]);
        }
    }

    // ============ RIDE MANAGEMENT ============

    /**
     * Get driver's rides
     */
    public function rides(Request $request, string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $filters = [
                'status' => $request->get('status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'limit' => $request->get('limit', 20)
            ];

            $rides = $this->driverService->getDriverRides($firebaseUid, $filters);
            $rideStats = $this->driverService->getDriverRideStatistics($firebaseUid);
            
            return response()->json([
                'success' => true,
                'rides' => $rides,
                'statistics' => $rideStats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting driver rides: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading rides: ' . $e->getMessage()
            ]);
        }
    }

    // ============ ACTIVITY MANAGEMENT ============

    /**
     * Get driver's activities
     */
    public function activities(Request $request, string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $filters = [
                'category' => $request->get('category'),
                'type' => $request->get('type'),
                'limit' => $request->get('limit', 50)
            ];

            $activities = $this->driverService->getDriverActivities($firebaseUid, $filters);
            
            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting driver activities: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading activities: ' . $e->getMessage()
            ]);
        }
    }

    // ============ LOCATION MANAGEMENT ============

    /**
     * Update driver location
     */
    public function updateLocation(Request $request, string $firebaseUid)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid location data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $result = $this->driverService->updateDriverLocation(
                $firebaseUid,
                $request->latitude,
                $request->longitude,
                $request->address
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Location updated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update location'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating driver location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating location: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get nearby drivers
     */
    public function nearbyDrivers(Request $request)
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|between:0.1,100',
            'availability_status' => 'nullable|in:available,busy,offline'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid location data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $filters = [];
            if ($request->availability_status) {
                $filters['availability_status'] = $request->availability_status;
            }

            $drivers = $this->driverService->getDriversNearLocation(
                $request->latitude,
                $request->longitude,
                $request->get('radius', 10),
                $filters
            );

            return response()->json([
                'success' => true,
                'drivers' => $drivers,
                'count' => count($drivers)
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting nearby drivers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error finding nearby drivers: ' . $e->getMessage()
            ]);
        }
    }

    // ============ STATISTICS ============

    /**
     * Get driver statistics
     */
    public function statistics()
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        try {
            $stats = $this->driverService->getDriverStatistics();
            
            return view('driver::statistics', compact('stats'));

        } catch (\Exception $e) {
            Log::error('Error getting driver statistics: ' . $e->getMessage());
            return redirect()->route('driver.index')->with('error', 'Error loading statistics.');
        }
    }

    /**
     * Get driver statistics API
     */
    public function statisticsApi()
    {
        if (!session('firebase_user')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $stats = $this->driverService->getDriverStatistics();
            
            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting driver statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading statistics: ' . $e->getMessage()
            ]);
        }
    }

    // ============ SYNC AND IMPORT ============

    /**
     * Sync drivers from Firebase
     */
    public function syncFirebase(Request $request)
    {
        if (!session('firebase_user')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        try {
            Log::info('Firebase drivers sync initiated by: ' . session('firebase_user.email'));

            $result = $this->driverService->syncFirebaseDrivers();

            if ($result['success']) {
                Log::info('Firebase drivers synced successfully', ['synced_count' => $result['synced_count']]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => "Successfully synced {$result['synced_count']} drivers from Firebase",
                        'synced_count' => $result['synced_count']
                    ]);
                }
                
                return redirect()->route('driver.index')->with('success', "Successfully synced {$result['synced_count']} drivers from Firebase");
            } else {
                Log::error('Firebase drivers sync failed', ['error' => $result['message']]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message']
                    ]);
                }
                
                return redirect()->route('driver.index')->with('error', 'Failed to sync drivers: ' . $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Firebase drivers sync exception: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error syncing drivers: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->route('driver.index')->with('error', 'Error syncing drivers: ' . $e->getMessage());
        }
    }

    // ============ EXPORT ============

    /**
     * Export drivers data
     */
    public function export(Request $request)
    {
        if (!session('firebase_user')) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel,pdf',
            'status' => 'nullable|in:active,inactive,suspended,pending',
            'verification_status' => 'nullable|in:pending,verified,rejected',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date|after_or_equal:created_from'
        ]);

        if ($validator->fails()) {
            return redirect()->route('driver.index')->with('error', 'Invalid export parameters.');
        }

        try {
            // Get filtered drivers
            $filters = [
                'status' => $request->status,
                'verification_status' => $request->verification_status,
                'limit' => 1000
            ];

            $drivers = $this->driverService->getAllDrivers($filters);
            
            // Filter by date range if provided
            if ($request->created_from || $request->created_to) {
                $drivers = collect($drivers)->filter(function($driver) use ($request) {
                    $createdAt = \Carbon\Carbon::parse($driver['created_at'] ?? now());
                    
                    if ($request->created_from && $createdAt->lt(\Carbon\Carbon::parse($request->created_from))) {
                        return false;
                    }
                    
                    if ($request->created_to && $createdAt->gt(\Carbon\Carbon::parse($request->created_to))) {
                        return false;
                    }
                    
                    return true;
                })->values()->toArray();
            }

            $format = $request->format;
            $filename = 'drivers_export_' . now()->format('Y_m_d_H_i_s') . '.' . $format;
            
            if ($format === 'csv') {
                return $this->exportCsv($drivers, $filename);
            }
            
            // For other formats, you can implement similar methods
            return redirect()->route('driver.index')->with('error', 'Export format not yet implemented.');

        } catch (\Exception $e) {
            Log::error('Error exporting drivers: ' . $e->getMessage());
            return redirect()->route('driver.index')->with('error', 'Error exporting drivers: ' . $e->getMessage());
        }
    }

    // ============ HELPER METHODS ============

    /**
     * Get bulk action success message
     */
    private function getBulkActionMessage(string $action, int $processed, int $failed): string
    {
        $actionPast = [
            'activate' => 'activated',
            'deactivate' => 'deactivated',
            'suspend' => 'suspended',
            'verify' => 'verified',
            'delete' => 'deleted'
        ][$action];

        $message = "Successfully {$actionPast} {$processed} drivers";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }
        
        return $message . ".";
    }

    /**
     * Export drivers to CSV
     */
    private function exportCsv(array $drivers, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($drivers) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Firebase UID', 'Name', 'Email', 'Phone', 'License Number',
                'Status', 'Verification Status', 'Availability Status',
                'Rating', 'Total Rides', 'Completed Rides', 'Total Earnings',
                'City', 'State', 'Country', 'Join Date', 'Created At'
            ]);

            foreach ($drivers as $driver) {
                fputcsv($file, [
                    $driver['firebase_uid'] ?? '',
                    $driver['name'] ?? '',
                    $driver['email'] ?? '',
                    $driver['phone'] ?? '',
                    $driver['license_number'] ?? '',
                    $driver['status'] ?? '',
                    $driver['verification_status'] ?? '',
                    $driver['availability_status'] ?? '',
                    $driver['rating'] ?? 0,
                    $driver['total_rides'] ?? 0,
                    $driver['completed_rides'] ?? 0,
                    $driver['total_earnings'] ?? 0,
                    $driver['city'] ?? '',
                    $driver['state'] ?? '',
                    $driver['country'] ?? '',
                    $driver['join_date'] ?? '',
                    $driver['created_at'] ?? '',
                ]);
            }

            fclose($file);
        };

        Log::info('Drivers exported to CSV', [
            'driver_count' => count($drivers),
            'exported_by' => session('firebase_user.email'),
            'filename' => $filename
        ]);

        return response()->stream($callback, 200, $headers);
    }
}