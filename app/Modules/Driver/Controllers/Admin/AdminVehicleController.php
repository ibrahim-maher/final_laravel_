<?php

namespace App\Modules\Driver\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Driver\Services\DriverVehicleService;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Driver\Services\DriverDocumentService;
use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\DriverDocument;

class AdminVehicleController extends Controller
{
    protected $vehicleService;
    protected $driverService;
    protected $documentService;

    public function __construct(
        DriverVehicleService $vehicleService,
        DriverService $driverService,
        DriverDocumentService $documentService
    ) {
        $this->vehicleService = $vehicleService;
        $this->driverService = $driverService;
        $this->documentService = $documentService;
    }

    // ============ DASHBOARD AND LISTING ============

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
                'fuel_type' => $request->get('fuel_type'),
                'driver_id' => $request->get('driver_id'),
                'limit' => min($request->get('limit', 15), 25)
            ];

            // Get vehicles directly without caching for list
            $vehicles = $this->vehicleService->getAllVehicles($filters);

            // Debug logging
            Log::info('Vehicles data structure', [
                'vehicles_type' => gettype($vehicles),
                'vehicles_class' => get_class($vehicles),
                'vehicles_count' => method_exists($vehicles, 'count') ? $vehicles->count() : count($vehicles)
            ]);

            // Cache total count for 5 minutes
            $totalVehicles = cache()->remember('total_vehicles_count', 300, function () {
                return $this->vehicleService->getTotalVehiclesCount();
            });

            // Cache statistics for 5 minutes
            $statistics = cache()->remember('vehicle_statistics', 300, function () {
                return $this->vehicleService->getVehicleStatistics();
            });

            Log::info('Admin vehicle dashboard accessed', [
                'admin' => session('firebase_user.email'),
                'filters' => $filters,
                'result_count' => $vehicles->count(),
                'total_vehicles' => $totalVehicles
            ]);

            return view('driver::admin.vehicles.index', compact(
                'vehicles',
                'totalVehicles',
                'statistics'
            ) + $filters);
        } catch (\Exception $e) {
            Log::error('Error loading admin vehicle dashboard: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error loading vehicle dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed vehicle information
     */
    public function show($vehicleId)
    {
        try {
            // Cache vehicle details for 2 minutes
            $cacheKey = "vehicle_details_{$vehicleId}";
            $vehicleData = cache()->remember($cacheKey, 120, function () use ($vehicleId) {

                $vehicle = $this->vehicleService->getVehicleById($vehicleId);
                if (!$vehicle) {
                    return null;
                }

                return [
                    'vehicle' => $vehicle,
                    'driver' => $this->driverService->getDriverById($vehicle->driver_firebase_uid),
                    'completionStatus' => $this->vehicleService->getVehicleCompletionStatus($vehicleId)
                ];
            });

            if (!$vehicleData || !$vehicleData['vehicle']) {
                return redirect()->route('admin.vehicles.index')
                    ->with('error', 'Vehicle not found.');
            }

            // Load secondary data with longer cache
            $secondaryData = cache()->remember("vehicle_secondary_{$vehicleId}", 300, function () use ($vehicleId) {
                return [
                    'rideStats' => $this->vehicleService->getVehicleRideStatistics($vehicleId),
                    'performanceMetrics' => $this->vehicleService->getVehiclePerformanceMetrics($vehicleId)
                ];
            });

            // Load documents and activities
            $dynamicData = cache()->remember("vehicle_dynamic_{$vehicleId}", 60, function () use ($vehicleId) {
                return [
                    'documents' => $this->vehicleService->getVehicleDocuments($vehicleId),
                    'activities' => $this->vehicleService->getVehicleActivities($vehicleId, ['limit' => 10]),
                ];
            });

            // Load recent rides
            $recentRides = cache()->remember("vehicle_recent_rides_{$vehicleId}", 180, function () use ($vehicleId) {
                return $this->vehicleService->getVehicleRides($vehicleId, ['limit' => 5]);
            });

            // Merge all data
            $allData = array_merge($vehicleData, $secondaryData, $dynamicData, [
                'rides' => $recentRides
            ]);

            Log::info('Admin viewed vehicle details', [
                'admin' => session('firebase_user.email'),
                'vehicle_id' => $vehicleId
            ]);

            return view('driver::admin.vehicles.show', $allData);
        } catch (\Exception $e) {
            Log::error('Error loading vehicle details: ' . $e->getMessage());
            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Error loading vehicle details.');
        }
    }

    // ============ STATISTICS ============

    /**
     * Display vehicle statistics dashboard
     */
    public function statistics()
    {
        try {
            // Get comprehensive vehicle statistics
            $stats = cache()->remember('vehicle_detailed_statistics', 300, function () {
                return $this->vehicleService->getDetailedVehicleStatistics();
            });

            // Get vehicle distribution by status
            $statusDistribution = cache()->remember('vehicle_status_distribution', 300, function () {
                return $this->vehicleService->getVehicleStatusDistribution();
            });

            // Get vehicle distribution by type
            $typeDistribution = cache()->remember('vehicle_type_distribution', 300, function () {
                return $this->vehicleService->getVehicleTypeDistribution();
            });

            // Get monthly vehicle registrations
            $monthlyRegistrations = cache()->remember('vehicle_monthly_registrations', 300, function () {
                return $this->vehicleService->getMonthlyVehicleRegistrations(12); // Last 12 months
            });

            // Get top performing vehicles
            $topPerformingVehicles = cache()->remember('top_performing_vehicles', 300, function () {
                return $this->vehicleService->getTopPerformingVehicles(10);
            });

            // Get vehicles needing attention (expired documents, etc.)
            $vehiclesNeedingAttention = $this->vehicleService->getVehiclesNeedingAttention();

            Log::info('Admin accessed vehicle statistics dashboard', [
                'admin' => session('firebase_user.email'),
                'timestamp' => now()
            ]);

            return view('driver::admin.vehicles.statistics', compact(
                'stats',
                'statusDistribution',
                'typeDistribution',
                'monthlyRegistrations',
                'topPerformingVehicles',
                'vehiclesNeedingAttention'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading vehicle statistics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Error loading vehicle statistics: ' . $e->getMessage());
        }
    }

    // ============ AJAX ENDPOINTS ============

    /**
     * AJAX endpoint to load vehicle tabs dynamically
     */
    public function loadTab(Request $request, $vehicleId, string $tab)
    {
        try {
            $data = [];

            switch ($tab) {
                case 'rides':
                    $data = cache()->remember("vehicle_all_rides_{$vehicleId}", 180, function () use ($vehicleId) {
                        return $this->vehicleService->getVehicleRides($vehicleId, ['limit' => 50]);
                    });
                    break;

                case 'activities':
                    $data = cache()->remember("vehicle_all_activities_{$vehicleId}", 120, function () use ($vehicleId) {
                        return $this->vehicleService->getVehicleActivities($vehicleId, ['limit' => 50]);
                    });
                    break;

                case 'documents':
                    $data = $this->vehicleService->getVehicleDocuments($vehicleId);
                    break;

                case 'maintenance':
                    $data = $this->vehicleService->getVehicleMaintenanceRecords($vehicleId);
                    break;

                default:
                    return response()->json(['error' => 'Invalid tab'], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading vehicle tab: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }

    /**
     * Update vehicle status (AJAX)
     */
    public function updateStatus(Request $request, $vehicleId)
    {
        Log::info('AdminVehicleController: updateStatus START', [
            'vehicle_id' => $vehicleId,
            'status' => $request->status,
            'admin' => session('firebase_user.email'),
            'timestamp' => now()->toDateTimeString()
        ]);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:activate,deactivate,suspend,verify,active,inactive,suspended,maintenance'
        ]);

        if ($validator->fails()) {
            Log::warning('AdminVehicleController: Validation failed', [
                'vehicle_id' => $vehicleId,
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

            Log::info('AdminVehicleController: Calling updateVehicleStatus', [
                'vehicle_id' => $vehicleId,
                'actual_status' => $actualStatus
            ]);

            // This will automatically clear cache
            $result = $this->vehicleService->updateVehicleStatus($vehicleId, $actualStatus);

            Log::info('AdminVehicleController: updateVehicleStatus result', [
                'vehicle_id' => $vehicleId,
                'result' => $result ? 'SUCCESS' : 'FAILED'
            ]);

            // Handle verification separately if needed
            if ($request->status === 'verify') {
                Log::info('AdminVehicleController: Updating verification status', [
                    'vehicle_id' => $vehicleId
                ]);

                $verificationResult = $this->vehicleService->updateVehicleVerificationStatus(
                    $vehicleId,
                    'verified',
                    session('firebase_user.uid')
                );

                Log::info('AdminVehicleController: Verification update result', [
                    'vehicle_id' => $vehicleId,
                    'verification_result' => $verificationResult ? 'SUCCESS' : 'FAILED'
                ]);
            }

            if ($result) {
                Log::info('AdminVehicleController: Status update completed successfully', [
                    'vehicle_id' => $vehicleId,
                    'final_status' => $actualStatus
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle status updated successfully!',
                    'new_status' => $actualStatus,
                    'timestamp' => now()->toDateTimeString()
                ]);
            } else {
                Log::error('AdminVehicleController: Failed to update vehicle status', [
                    'vehicle_id' => $vehicleId,
                    'requested_status' => $actualStatus
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update vehicle status'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('AdminVehicleController: Exception in updateStatus', [
                'vehicle_id' => $vehicleId,
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
    public function updateVerificationStatus(Request $request, $vehicleId)
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
            $result = $this->vehicleService->updateVehicleVerificationStatus(
                $vehicleId,
                $request->verification_status,
                session('firebase_user.uid'),
                $request->notes
            );

            if ($result) {
                Log::info('Admin updated vehicle verification status', [
                    'admin' => session('firebase_user.email'),
                    'vehicle_id' => $vehicleId,
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
     * Show form for creating new vehicle
     */
    public function create()
    {
        $drivers = Driver::active()->get();

        return view('driver::admin.vehicles.create', [
            'drivers' => $drivers,
            'statuses' => Vehicle::getStatuses(),
            'verificationStatuses' => Vehicle::getVerificationStatuses(),
            'vehicleTypes' => Vehicle::getVehicleTypes(),
            'fuelTypes' => Vehicle::getFuelTypes(),
            'documentTypes' => DriverDocument::getDocumentTypes()
        ]);
    }

    /**
     * Store newly created vehicle
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_firebase_uid' => 'required|string|exists:drivers,firebase_uid',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate',
            'vin' => 'nullable|string|max:17|unique:vehicles,vin',
            'vehicle_type' => 'required|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'seats' => 'nullable|integer|min:2|max:50',
            'registration_number' => 'nullable|string|max:50',
            'registration_expiry' => 'nullable|date|after:today',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_policy_number' => 'nullable|string|max:50',
            'insurance_expiry' => 'nullable|date|after:today',
            'status' => 'required|in:active,inactive,suspended,maintenance',
            'verification_status' => 'required|in:pending,verified,rejected',
            'is_primary' => 'boolean',

            // Document validation
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
            // Prepare vehicle data
            $vehicleData = $this->prepareVehicleData($request);

            // Create vehicle
            $vehicle = $this->vehicleService->createVehicle($vehicleData);

            if (!$vehicle) {
                return redirect()->back()
                    ->with('error', 'Failed to create vehicle.')
                    ->withInput();
            }

            // Upload documents if provided
            $this->uploadVehicleDocuments($request, $vehicle->id);

            Log::info('Admin created vehicle', [
                'admin' => session('firebase_user.email'),
                'vehicle_id' => $vehicle->id,
                'driver_id' => $request->driver_firebase_uid,
                'documents_uploaded' => $this->countUploadedFiles($request)
            ]);

            return redirect()->route('admin.vehicles.show', $vehicle->id)
                ->with('success', 'Vehicle created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating vehicle: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Error creating vehicle: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing vehicle
     */
    public function edit($vehicleId)
    {
        try {
            $vehicle = $this->vehicleService->getVehicleById($vehicleId);

            if (!$vehicle) {
                return redirect()->route('admin.vehicles.index')
                    ->with('error', 'Vehicle not found.');
            }

            $drivers = Driver::active()->get();
            $documents = $this->vehicleService->getVehicleDocuments($vehicleId);

            return view('driver::admin.vehicles.edit', [
                'vehicle' => $vehicle,
                'drivers' => $drivers,
                'documents' => $documents,
                'statuses' => Vehicle::getStatuses(),
                'verificationStatuses' => Vehicle::getVerificationStatuses(),
                'vehicleTypes' => Vehicle::getVehicleTypes(),
                'fuelTypes' => Vehicle::getFuelTypes(),
                'documentTypes' => DriverDocument::getDocumentTypes()
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
    public function update(Request $request, $vehicleId)
    {
        $validator = Validator::make($request->all(), [
            'driver_firebase_uid' => 'required|string|exists:drivers,firebase_uid',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate,' . $vehicleId,
            'vin' => 'nullable|string|max:17|unique:vehicles,vin,' . $vehicleId,
            'vehicle_type' => 'required|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'seats' => 'nullable|integer|min:2|max:50',
            'registration_number' => 'nullable|string|max:50',
            'registration_expiry' => 'nullable|date|after:today',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_policy_number' => 'nullable|string|max:50',
            'insurance_expiry' => 'nullable|date|after:today',
            'status' => 'required|in:active,inactive,suspended,maintenance',
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
            $vehicleData['updated_by'] = session('firebase_user.uid');

            $result = $this->vehicleService->updateVehicle($vehicleId, $vehicleData);

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
    public function destroy($vehicleId)
    {
        try {
            $vehicle = $this->vehicleService->getVehicleById($vehicleId);

            if (!$vehicle) {
                return redirect()->route('admin.vehicles.index')
                    ->with('error', 'Vehicle not found.');
            }

            $result = $this->vehicleService->deleteVehicle($vehicleId);

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

    // ============ BULK OPERATIONS ============

    /**
     * Bulk operations on vehicles
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,suspend,verify,delete',
            'vehicle_ids' => 'required|array|min:1',
            'vehicle_ids.*' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input'
            ], 400);
        }

        try {
            $result = $this->vehicleService->performBulkAction(
                $request->action,
                $request->vehicle_ids
            );

            if ($result['success']) {
                Log::info('Admin performed bulk action on vehicles', [
                    'admin' => session('firebase_user.email'),
                    'action' => $request->action,
                    'count' => count($request->vehicle_ids)
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
     * Export vehicles data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel',
            'status' => 'nullable|in:active,inactive,suspended,maintenance',
            'verification_status' => 'nullable|in:pending,verified,rejected',
            'vehicle_type' => 'nullable|string',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date|after_or_equal:created_from'
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Invalid export parameters.');
        }

        try {
            $filters = [
                'status' => $request->status,
                'verification_status' => $request->verification_status,
                'vehicle_type' => $request->vehicle_type,
                'created_from' => $request->created_from,
                'created_to' => $request->created_to
            ];

            $vehicles = $this->vehicleService->exportVehicles($filters);

            $filename = 'vehicles_export_' . now()->format('Y_m_d_H_i_s') . '.csv';

            Log::info('Admin exported vehicles', [
                'admin' => session('firebase_user.email'),
                'count' => count($vehicles),
                'filters' => $filters
            ]);

            return $this->generateCsvExport($vehicles, $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting vehicles: ' . $e->getMessage());
            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Error exporting vehicles: ' . $e->getMessage());
        }
    }

    // ============ HELPER METHODS ============

    /**
     * Prepare vehicle data from request
     */
    private function prepareVehicleData(Request $request): array
    {
        return [
            'driver_firebase_uid' => $request->driver_firebase_uid,
            'make' => $request->make,
            'model' => $request->model,
            'year' => $request->year,
            'color' => $request->color,
            'license_plate' => $request->license_plate,
            'vin' => $request->vin,
            'vehicle_type' => $request->vehicle_type,
            'fuel_type' => $request->fuel_type,
            'seats' => $request->seats ?? 4,
            'registration_number' => $request->registration_number,
            'registration_expiry' => $request->registration_expiry,
            'insurance_provider' => $request->insurance_provider,
            'insurance_policy_number' => $request->insurance_policy_number,
            'insurance_expiry' => $request->insurance_expiry,
            'status' => $request->status,
            'verification_status' => $request->verification_status,
            'is_primary' => $request->boolean('is_primary'),
            'created_by' => session('firebase_user.uid')
        ];
    }

    /**
     * Upload vehicle documents
     */
    private function uploadVehicleDocuments(Request $request, $vehicleId): void
    {
        $documentTypes = [
            'vehicle_registration_doc' => DriverDocument::TYPE_VEHICLE_REGISTRATION,
            'insurance_certificate' => DriverDocument::TYPE_INSURANCE_CERTIFICATE
        ];

        foreach ($documentTypes as $inputName => $documentType) {
            if ($request->hasFile($inputName)) {
                try {
                    $file = $request->file($inputName);
                    $documentData = [
                        'document_type' => $documentType,
                        'document_name' => $this->getDocumentName($inputName, $file->getClientOriginalName()),
                        'vehicle_id' => $vehicleId
                    ];

                    $result = $this->documentService->uploadVehicleDocument($vehicleId, $file, $documentData);

                    if ($result) {
                        Log::info('Document uploaded for vehicle', [
                            'vehicle_id' => $vehicleId,
                            'document_type' => $documentType,
                            'file_name' => $file->getClientOriginalName()
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to upload document for vehicle', [
                        'vehicle_id' => $vehicleId,
                        'document_type' => $documentType,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Handle multiple vehicle photos
        if ($request->hasFile('vehicle_photos')) {
            foreach ($request->file('vehicle_photos') as $index => $photo) {
                try {
                    $documentData = [
                        'document_type' => DriverDocument::TYPE_VEHICLE_PHOTO,
                        'document_name' => "Vehicle Photo " . ($index + 1),
                        'vehicle_id' => $vehicleId
                    ];

                    $this->documentService->uploadVehicleDocument($vehicleId, $photo, $documentData);
                } catch (\Exception $e) {
                    Log::warning('Failed to upload vehicle photo', [
                        'vehicle_id' => $vehicleId,
                        'photo_index' => $index,
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
        $fileFields = ['vehicle_registration_doc', 'insurance_certificate'];

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
            'activate' => "Activated {$processed} vehicles",
            'deactivate' => "Deactivated {$processed} vehicles",
            'suspend' => "Suspended {$processed} vehicles",
            'verify' => "Verified {$processed} vehicles",
            'delete' => "Deleted {$processed} vehicles"
        ];

        $message = $messages[$action] ?? "Processed {$processed} vehicles";

        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        return $message;
    }

    /**
     * Generate CSV export response
     */
    private function generateCsvExport($vehicles, $filename)
    {
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function () use ($vehicles) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Driver ID',
                'Make',
                'Model',
                'Year',
                'Color',
                'License Plate',
                'VIN',
                'Vehicle Type',
                'Fuel Type',
                'Seats',
                'Status',
                'Verification Status',
                'Is Primary',
                'Registration Number',
                'Registration Expiry',
                'Insurance Provider',
                'Insurance Expiry',
                'Created At'
            ]);

            foreach ($vehicles as $vehicle) {
                fputcsv($file, [
                    $vehicle->id ?? '',
                    $vehicle->driver_firebase_uid ?? '',
                    $vehicle->make ?? '',
                    $vehicle->model ?? '',
                    $vehicle->year ?? '',
                    $vehicle->color ?? '',
                    $vehicle->license_plate ?? '',
                    $vehicle->vin ?? '',
                    $vehicle->vehicle_type ?? '',
                    $vehicle->fuel_type ?? '',
                    $vehicle->seats ?? '',
                    $vehicle->status ?? '',
                    $vehicle->verification_status ?? '',
                    $vehicle->is_primary ? 'Yes' : 'No',
                    $vehicle->registration_number ?? '',
                    $vehicle->registration_expiry ?? '',
                    $vehicle->insurance_provider ?? '',
                    $vehicle->insurance_expiry ?? '',
                    $vehicle->created_at ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
