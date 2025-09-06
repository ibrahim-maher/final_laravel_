<?php

namespace App\Modules\Driver\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Driver\Services\DriverVehicleService;

use App\Modules\Driver\Models\Vehicle;

class AdminVehicleController extends Controller
{
    protected $driverService;
    protected $DriverVehicleService;

    public function __construct(DriverService $driverService, DriverVehicleService $DriverVehicleService)
    {
        $this->driverService = $driverService;
        $this->DriverVehicleService = $DriverVehicleService;
     
    }

    /**
     * Display vehicle management dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'verification_status' => $request->get('verification_status'),
                'vehicle_type' => $request->get('vehicle_type'),
                'limit' => $request->get('limit', 50)
            ];

            // Get all vehicles from all drivers
            $allDrivers = $this->driverService->getAllDrivers(['limit' => 1000]);
            $vehicles = collect();
            
            foreach ($allDrivers as $driver) {
                $driverVehicles = $this->driverService->getDriverVehicles($driver['firebase_uid']);
                foreach ($driverVehicles as $vehicle) {
                    $vehicle['driver_name'] = $driver['name'];
                    $vehicle['driver_email'] = $driver['email'];
                    $vehicles->push($vehicle);
                }
            }

            // Apply filters
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $vehicles = $vehicles->filter(function($vehicle) use ($search) {
                    return stripos($vehicle['make'] ?? '', $search) !== false ||
                           stripos($vehicle['model'] ?? '', $search) !== false ||
                           stripos($vehicle['license_plate'] ?? '', $search) !== false ||
                           stripos($vehicle['driver_name'] ?? '', $search) !== false;
                });
            }

            if (!empty($filters['status'])) {
                $vehicles = $vehicles->where('status', $filters['status']);
            }

            if (!empty($filters['verification_status'])) {
                $vehicles = $vehicles->where('verification_status', $filters['verification_status']);
            }

            if (!empty($filters['vehicle_type'])) {
                $vehicles = $vehicles->where('vehicle_type', $filters['vehicle_type']);
            }

            // Paginate
            $currentPage = $request->get('page', 1);
            $perPage = $filters['limit'];
            $vehicles = $vehicles->forPage($currentPage, $perPage);

            $totalVehicles = $vehicles->count();
            
            Log::info('Admin vehicle dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters
            ]);
            
            return view('driver::admin.vehicles.index', compact(
                'vehicles', 
                'totalVehicles'
            ) + $filters + [
                'vehicleTypes' => Vehicle::getVehicleTypes(),
                'fuelTypes' => Vehicle::getFuelTypes(),
                'statuses' => $this->getVehicleStatuses(),
                'verificationStatuses' => $this->getVerificationStatuses()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading admin vehicle dashboard: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading vehicle dashboard.');
        }
    }

    /**
     * Show detailed vehicle information
     */
    public function show(string $vehicleId)
    {
        try {
            $vehicle = $this->DriverVehicleService->getVehicleById($vehicleId);
            
            if (!$vehicle) {
                return redirect()->route('admin.vehicles.index')
                    ->with('error', 'Vehicle not found.');
            }

            // Get driver information
            $driver = $this->driverService->getDriverById($vehicle['driver_firebase_uid']);
            
            // Get related rides
            $rides = $this->driverService->getDriverRides($vehicle['driver_firebase_uid'], ['limit' => 20]);

            Log::info('Admin viewed vehicle details', [
                'admin' => session('firebase_user.email'),
                'vehicle_id' => $vehicleId
            ]);
            
            return view('driver::admin.vehicles.show', compact(
                'vehicle',
                'driver',
                'rides'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error loading vehicle details: ' . $e->getMessage());
            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Error loading vehicle details.');
        }
    }

    /**
     * Show form for creating new vehicle
     */
    public function create(Request $request)
    {
        $driverFirebaseUid = $request->get('driver_firebase_uid');
        $driver = null;
        
        if ($driverFirebaseUid) {
            $driver = $this->driverService->getDriverById($driverFirebaseUid);
        }

        return view('driver::admin.vehicles.create', [
            'driver' => $driver,
            'vehicleTypes' => Vehicle::getVehicleTypes(),
            'fuelTypes' => Vehicle::getFuelTypes(),
            'statuses' => $this->getVehicleStatuses(),
            'verificationStatuses' => $this->getVerificationStatuses(),
            'drivers' => $this->driverService->getAllDrivers(['limit' => 1000])
        ]);
    }

    /**
     * Store newly created vehicle
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_firebase_uid' => 'required|string',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|between:1900,' . (date('Y') + 1),
            'color' => 'required|string|max:50',
            'license_plate' => 'required|string|max:20',
            'vin' => 'nullable|string|max:50',
            'vehicle_type' => 'required|in:' . implode(',', array_keys(Vehicle::getVehicleTypes())),
            'fuel_type' => 'nullable|in:' . implode(',', array_keys(Vehicle::getFuelTypes())),
            'transmission' => 'nullable|in:manual,automatic,cvt',
            'doors' => 'nullable|integer|between:2,6',
            'seats' => 'required|integer|between:1,20',
            'status' => 'required|in:active,inactive,maintenance,suspended',
            'verification_status' => 'required|in:pending,verified,rejected',
            'is_primary' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $vehicleData = $request->all();
            $vehicleData['created_by'] = session('firebase_user.uid');

            $result = $this->driverService->createVehicle($vehicleData);

            if ($result) {
                // Set as primary if requested
                if ($request->is_primary) {
                    $this->driverService->setPrimaryVehicle(
                        $request->driver_firebase_uid, 
                        $result['id']
                    );
                }

                Log::info('Admin created vehicle', [
                    'admin' => session('firebase_user.email'),
                    'vehicle_id' => $result['id'] ?? 'unknown',
                    'driver_id' => $request->driver_firebase_uid
                ]);
                
                return redirect()->route('admin.vehicles.index')
                    ->with('success', 'Vehicle created successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to create vehicle.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error creating vehicle: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error creating vehicle: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing vehicle
     */
    public function edit(string $vehicleId)
    {
        try {
            $vehicle = $this->DriverVehicleService->getVehicleById($vehicleId);
            
            if (!$vehicle) {
                return redirect()->route('admin.vehicles.index')
                    ->with('error', 'Vehicle not found.');
            }

            $driver = $this->driverService->getDriverById($vehicle['driver_firebase_uid']);

            return view('driver::admin.vehicles.edit', [
                'vehicle' => $vehicle,
                'driver' => $driver,
                'vehicleTypes' => Vehicle::getVehicleTypes(),
                'fuelTypes' => Vehicle::getFuelTypes(),
                'statuses' => $this->getVehicleStatuses(),
                'verificationStatuses' => $this->getVerificationStatuses()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading vehicle for edit: ' . $e->getMessage());
            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Error loading vehicle for editing.');
        }
    }

    /**
     * Update vehicle information
     */
    public function update(Request $request, string $vehicleId)
    {
        $validator = Validator::make($request->all(), [
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|between:1900,' . (date('Y') + 1),
            'color' => 'required|string|max:50',
            'license_plate' => 'required|string|max:20',
            'vin' => 'nullable|string|max:50',
            'vehicle_type' => 'required|in:' . implode(',', array_keys(Vehicle::getVehicleTypes())),
            'fuel_type' => 'nullable|in:' . implode(',', array_keys(Vehicle::getFuelTypes())),
            'transmission' => 'nullable|in:manual,automatic,cvt',
            'doors' => 'nullable|integer|between:2,6',
            'seats' => 'required|integer|between:1,20',
            'status' => 'required|in:active,inactive,maintenance,suspended',
            'verification_status' => 'required|in:pending,verified,rejected',
            'mileage' => 'nullable|integer|min:0',
            'condition_rating' => 'nullable|numeric|between:1,5',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $vehicleData = $request->all();
            $vehicleData['updated_by'] = session('firebase_user.uid');

            $result = $this->driverService->updateVehicle($vehicleId, $vehicleData);

            if ($result) {
                Log::info('Admin updated vehicle', [
                    'admin' => session('firebase_user.email'),
                    'vehicle_id' => $vehicleId
                ]);
                
                return redirect()->route('admin.vehicles.show', $vehicleId)
                    ->with('success', 'Vehicle updated successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to update vehicle.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error updating vehicle: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating vehicle: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete vehicle
     */
    public function destroy(string $vehicleId)
    {
        try {
            $vehicle = $this->DriverVehicleService->getVehicleById($vehicleId);
            
            if (!$vehicle) {
                return redirect()->route('admin.vehicles.index')
                    ->with('error', 'Vehicle not found.');
            }

            $result = $this->driverService->deleteVehicle($vehicleId);

            if ($result) {
                Log::info('Admin deleted vehicle', [
                    'admin' => session('firebase_user.email'),
                    'vehicle_id' => $vehicleId
                ]);
                
                return redirect()->route('admin.vehicles.index')
                    ->with('success', 'Vehicle deleted successfully!');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to delete vehicle.');
            }

        } catch (\Exception $e) {
            Log::error('Error deleting vehicle: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting vehicle: ' . $e->getMessage());
        }
    }

    /**
     * Update vehicle verification status (AJAX)
     */
    public function updateVerificationStatus(Request $request, string $vehicleId)
    {
        $validator = Validator::make($request->all(), [
            'verification_status' => 'required|in:pending,verified,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid verification status'
            ], 400);
        }

        try {
            $result = $this->driverService->updateVehicleVerificationStatus(
                $vehicleId, 
                $request->verification_status
            );

            if ($result) {
                Log::info('Admin updated vehicle verification status', [
                    'admin' => session('firebase_user.email'),
                    'vehicle_id' => $vehicleId,
                    'verification_status' => $request->verification_status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle verification status updated successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update verification status'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating vehicle verification status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating verification: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Set vehicle as primary (AJAX)
     */
    public function setPrimary(Request $request, string $vehicleId)
    {
        try {
            $vehicle = $this->DriverVehicleService->getVehicleById($vehicleId);
            
            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found'
                ], 404);
            }

            $result = $this->driverService->setPrimaryVehicle(
                $vehicle['driver_firebase_uid'], 
                $vehicleId
            );

            if ($result) {
                Log::info('Admin set primary vehicle', [
                    'admin' => session('firebase_user.email'),
                    'vehicle_id' => $vehicleId,
                    'driver_id' => $vehicle['driver_firebase_uid']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Primary vehicle set successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to set primary vehicle'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error setting primary vehicle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error setting primary vehicle: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk operations on vehicles
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:verify,reject,activate,deactivate,delete',
            'vehicle_ids' => 'required|array|min:1',
            'vehicle_ids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $action = $request->action;
            $vehicleIds = $request->vehicle_ids;
            
            $processedCount = 0;
            $failedCount = 0;

            foreach ($vehicleIds as $vehicleId) {
                try {
                    $success = $this->executeBulkVehicleAction($action, $vehicleId);
                    if ($success) {
                        $processedCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning('Bulk vehicle action failed', [
                        'vehicle_id' => $vehicleId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Admin performed bulk vehicle action', [
                'admin' => session('firebase_user.email'),
                'action' => $action,
                'processed' => $processedCount,
                'failed' => $failedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->getBulkVehicleActionMessage($action, $processedCount, $failedCount),
                'processed_count' => $processedCount,
                'failed_count' => $failedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk vehicle action error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Vehicle statistics
     */
    public function statistics()
    {
        try {
            $statistics = $this->driverService->getSystemAnalytics()['vehicle_statistics'] ?? [];
            
            return view('driver::admin.vehicles.statistics', compact('statistics'));

        } catch (\Exception $e) {
            Log::error('Error loading vehicle statistics: ' . $e->getMessage());
            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Error loading statistics.');
        }
    }

    /**
     * Helper: Execute bulk vehicle action
     */
    private function executeBulkVehicleAction(string $action, string $vehicleId): bool
    {
        switch ($action) {
            case 'verify':
                return $this->driverService->updateVehicleVerificationStatus($vehicleId, 'verified');
            case 'reject':
                return $this->driverService->updateVehicleVerificationStatus($vehicleId, 'rejected');
            case 'activate':
                return $this->driverService->updateVehicle($vehicleId, ['status' => 'active']);
            case 'deactivate':
                return $this->driverService->updateVehicle($vehicleId, ['status' => 'inactive']);
            case 'delete':
                return $this->driverService->deleteVehicle($vehicleId);
            default:
                return false;
        }
    }

    /**
     * Helper: Get bulk vehicle action message
     */
    private function getBulkVehicleActionMessage(string $action, int $processed, int $failed): string
    {
        $actionPast = [
            'verify' => 'verified',
            'reject' => 'rejected', 
            'activate' => 'activated',
            'deactivate' => 'deactivated',
            'delete' => 'deleted'
        ][$action];

        $message = "Successfully {$actionPast} {$processed} vehicles";
        
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }
        
        return $message . ".";
    }

    /**
     * Helper: Get vehicle statuses
     */
    private function getVehicleStatuses(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'maintenance' => 'Under Maintenance',
            'suspended' => 'Suspended'
        ];
    }

    /**
     * Helper: Get verification statuses
     */
    private function getVerificationStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'verified' => 'Verified',
            'rejected' => 'Rejected'
        ];
    }
}
