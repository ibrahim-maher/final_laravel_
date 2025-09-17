<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\Ride;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DriverVehicleService
{
    protected $firestoreService;

    public function __construct()
    {
        $this->firestoreService = new FirestoreService();
    }

    /**
     * Get all vehicles with filters
     */
    public function getAllVehicles(array $filters = [])
    {
        try {
            $query = Vehicle::with('driver');

            // Apply filters
            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('make', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('model', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('license_plate', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('vin', 'like', '%' . $filters['search'] . '%')
                        ->orWhereHas('driver', function ($dq) use ($filters) {
                            $dq->where('name', 'like', '%' . $filters['search'] . '%');
                        });
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['verification_status'])) {
                $query->where('verification_status', $filters['verification_status']);
            }

            if (!empty($filters['vehicle_type'])) {
                $query->where('vehicle_type', $filters['vehicle_type']);
            }

            if (!empty($filters['fuel_type'])) {
                $query->where('fuel_type', $filters['fuel_type']);
            }

            if (!empty($filters['driver_id'])) {
                $query->where('driver_firebase_uid', $filters['driver_id']);
            }

            $limit = $filters['limit'] ?? 50;

            return $query->orderBy('created_at', 'desc')->paginate($limit);
        } catch (\Exception $e) {
            Log::error('Error getting all vehicles: ' . $e->getMessage());
            return Vehicle::paginate(0); // Return empty paginator
        }
    }

    /**
     * Get vehicle by ID
     */
    public function getVehicleById($vehicleId)
    {
        try {
            return Vehicle::with('driver')->find($vehicleId);
        } catch (\Exception $e) {
            Log::error('Error getting vehicle by ID: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId
            ]);
            return null;
        }
    }

    /**
     * Get vehicles by driver
     */
    public function getDriverVehicles($driverFirebaseUid)
    {
        try {
            return Vehicle::where('driver_firebase_uid', $driverFirebaseUid)
                ->orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting driver vehicles: ' . $e->getMessage(), [
                'driver_uid' => $driverFirebaseUid
            ]);
            return collect([]);
        }
    }

    /**
     * Create new vehicle
     */
    public function createVehicle(array $data)
    {
        DB::beginTransaction();
        try {
            // Validate required fields
            $requiredFields = ['driver_firebase_uid', 'make', 'model', 'year', 'license_plate'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            // Check if driver exists
            $driver = Driver::where('firebase_uid', $data['driver_firebase_uid'])->first();
            if (!$driver) {
                throw new \Exception('Driver not found');
            }

            // If this is set as primary, unset other primary vehicles for this driver
            if (!empty($data['is_primary'])) {
                Vehicle::where('driver_firebase_uid', $data['driver_firebase_uid'])
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            // Set defaults
            $data = array_merge([
                'status' => Vehicle::STATUS_ACTIVE,
                'verification_status' => Vehicle::VERIFICATION_PENDING,
                'is_primary' => false,
                'seats' => 4,
                'created_by' => session('firebase_user.uid', 'system')
            ], $data);

            $vehicle = Vehicle::create($data);

            // Sync to Firebase if needed
            $this->syncVehicleToFirebase($vehicle);

            // Create activity log
            $this->createVehicleActivity($data['driver_firebase_uid'], 'vehicle_created', [
                'title' => 'Vehicle Added',
                'description' => "New vehicle added: {$vehicle->full_name}",
                'vehicle_id' => $vehicle->id,
                'vehicle_info' => $vehicle->full_name
            ]);

            DB::commit();

            Log::info('Vehicle created successfully', [
                'vehicle_id' => $vehicle->id,
                'driver_uid' => $data['driver_firebase_uid']
            ]);

            return $vehicle;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating vehicle: ' . $e->getMessage(), [
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update vehicle
     */
    public function updateVehicle($vehicleId, array $data)
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            Log::error('Vehicle not found for update', ['vehicle_id' => $vehicleId]);
            return false;
        }

        DB::beginTransaction();
        try {
            // If this is set as primary, unset other primary vehicles for this driver
            if (!empty($data['is_primary'])) {
                Vehicle::where('driver_firebase_uid', $vehicle->driver_firebase_uid)
                    ->where('id', '!=', $vehicleId)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $data['updated_by'] = session('firebase_user.uid', 'system');
            $vehicle->update($data);

            // Sync to Firebase
            $this->syncVehicleToFirebase($vehicle);

            // Create activity
            $this->createVehicleActivity($vehicle->driver_firebase_uid, 'vehicle_updated', [
                'title' => 'Vehicle Updated',
                'description' => "Vehicle information updated: {$vehicle->full_name}",
                'vehicle_id' => $vehicle->id,
                'vehicle_info' => $vehicle->full_name
            ]);

            // Clear cache
            $this->clearVehicleCache($vehicleId);

            DB::commit();

            Log::info('Vehicle updated successfully', [
                'vehicle_id' => $vehicleId,
                'updated_fields' => array_keys($data)
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating vehicle: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Update vehicle status
     */
    public function updateVehicleStatus($vehicleId, $status)
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            Log::error('Vehicle not found for status update', ['vehicle_id' => $vehicleId]);
            return false;
        }

        try {
            $vehicle->update([
                'status' => $status,
                'updated_by' => session('firebase_user.uid', 'system')
            ]);

            // Sync to Firebase
            $this->syncVehicleToFirebase($vehicle);

            // Create activity
            $this->createVehicleActivity($vehicle->driver_firebase_uid, 'status_change', [
                'title' => 'Vehicle Status Changed',
                'description' => "Vehicle status changed to: " . ucfirst($status),
                'vehicle_id' => $vehicle->id,
                'vehicle_info' => $vehicle->full_name,
                'new_status' => $status
            ]);

            // Clear cache
            $this->clearVehicleCache($vehicleId);

            Log::info('Vehicle status updated successfully', [
                'vehicle_id' => $vehicleId,
                'new_status' => $status
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating vehicle status: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId,
                'status' => $status
            ]);
            return false;
        }
    }

    /**
     * Update vehicle verification status
     */
    public function updateVehicleVerificationStatus($vehicleId, $status, $adminUid = null, $notes = null)
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            Log::error('Vehicle not found for verification update', ['vehicle_id' => $vehicleId]);
            return false;
        }

        try {
            $updateData = [
                'verification_status' => $status,
                'verification_date' => $status === 'verified' ? now() : null,
                'verified_by' => $adminUid,
                'updated_by' => $adminUid ?? session('firebase_user.uid', 'system')
            ];

            if ($notes) {
                $updateData['verification_notes'] = $notes;
            }

            $vehicle->update($updateData);

            // Sync to Firebase
            $this->syncVehicleToFirebase($vehicle);

            // Create activity
            $this->createVehicleActivity($vehicle->driver_firebase_uid, 'verification_update', [
                'title' => 'Vehicle Verification Updated',
                'description' => "Vehicle verification status changed to: " . ucfirst($status),
                'vehicle_id' => $vehicle->id,
                'vehicle_info' => $vehicle->full_name,
                'new_status' => $status,
                'admin_uid' => $adminUid,
                'notes' => $notes
            ]);

            // Clear cache
            $this->clearVehicleCache($vehicleId);

            Log::info('Vehicle verification status updated successfully', [
                'vehicle_id' => $vehicleId,
                'new_status' => $status
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating vehicle verification status: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId,
                'status' => $status
            ]);
            return false;
        }
    }

    /**
     * Delete vehicle
     */
    public function deleteVehicle($vehicleId)
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            Log::error('Vehicle not found for deletion', ['vehicle_id' => $vehicleId]);
            return false;
        }

        try {
            // Create activity before deletion
            $this->createVehicleActivity($vehicle->driver_firebase_uid, 'vehicle_deleted', [
                'title' => 'Vehicle Removed',
                'description' => "Vehicle removed: {$vehicle->full_name}",
                'vehicle_id' => $vehicle->id,
                'vehicle_info' => $vehicle->full_name
            ]);

            // Delete from Firebase first
            $this->deleteVehicleFromFirebase($vehicle->id);

            // Clear cache
            $this->clearVehicleCache($vehicleId);

            // Delete from database
            $result = $vehicle->delete();

            Log::info('Vehicle deleted successfully', ['vehicle_id' => $vehicleId]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error deleting vehicle: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId
            ]);
            return false;
        }
    }

    /**
     * Get vehicle documents
     */
    public function getVehicleDocuments($vehicleId)
    {
        try {
            return DriverDocument::where('vehicle_id', $vehicleId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting vehicle documents: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId
            ]);
            return collect([]);
        }
    }

    /**
     * Get vehicle rides
     */
    public function getVehicleRides($vehicleId, array $filters = [])
    {
        try {
            // Since rides are in Firestore, we'll simulate this
            // In a real implementation, you'd query Firestore for rides by vehicle_id
            return [];
        } catch (\Exception $e) {
            Log::error('Error getting vehicle rides: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId
            ]);
            return [];
        }
    }

    /**
     * Get vehicle activities
     */
    public function getVehicleActivities($vehicleId, array $filters = [])
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            return collect([]);
        }

        try {
            // Get activities from Firestore
            $activities = $this->firestoreService
                ->collection('vehicle_activities')
                ->where('vehicle_id', '==', $vehicleId)
                ->orderBy('created_at', 'desc')
                ->limit($filters['limit'] ?? 30)
                ->get();

            return collect($activities ?? []);
        } catch (\Exception $e) {
            Log::error('Error getting vehicle activities: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId
            ]);
            return collect([]);
        }
    }

    /**
     * Get vehicle maintenance records
     */
    public function getVehicleMaintenanceRecords($vehicleId)
    {
        try {
            // This would typically come from a maintenance_records table or Firestore collection
            // For now, return empty collection
            return collect([]);
        } catch (\Exception $e) {
            Log::error('Error getting vehicle maintenance records: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get vehicle statistics
     */
    public function getVehicleStatistics()
    {
        try {
            return [
                'total_vehicles' => Vehicle::count(),
                'active_vehicles' => Vehicle::where('status', Vehicle::STATUS_ACTIVE)->count(),
                'verified_vehicles' => Vehicle::where('verification_status', Vehicle::VERIFICATION_VERIFIED)->count(),
                'pending_verification' => Vehicle::where('verification_status', Vehicle::VERIFICATION_PENDING)->count(),
                'maintenance_vehicles' => Vehicle::where('status', Vehicle::STATUS_MAINTENANCE)->count(),
                'suspended_vehicles' => Vehicle::where('status', Vehicle::STATUS_SUSPENDED)->count(),
                'recent_registrations' => Vehicle::where('created_at', '>=', now()->subDays(7))->count(),
                'expired_registrations' => Vehicle::whereDate('registration_expiry', '<', now())->count(),
                'expired_insurance' => Vehicle::whereDate('insurance_expiry', '<', now())->count(),
                'expiring_soon_registration' => Vehicle::whereBetween('registration_expiry', [now(), now()->addDays(30)])->count(),
                'expiring_soon_insurance' => Vehicle::whereBetween('insurance_expiry', [now(), now()->addDays(30)])->count(),
                'vehicle_types_breakdown' => Vehicle::selectRaw('vehicle_type, COUNT(*) as count')
                    ->whereNotNull('vehicle_type')
                    ->groupBy('vehicle_type')
                    ->pluck('count', 'vehicle_type')
                    ->toArray(),
                'fuel_types_breakdown' => Vehicle::selectRaw('fuel_type, COUNT(*) as count')
                    ->whereNotNull('fuel_type')
                    ->groupBy('fuel_type')
                    ->pluck('count', 'fuel_type')
                    ->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('Error getting vehicle statistics: ' . $e->getMessage());
            return $this->getDefaultStatistics();
        }
    }

    /**
     * Get detailed vehicle statistics
     */
    public function getDetailedVehicleStatistics()
    {
        try {
            return [
                'overview' => [
                    'total_vehicles' => Vehicle::count(),
                    'active_vehicles' => Vehicle::where('status', 'active')->count(),
                    'inactive_vehicles' => Vehicle::where('status', 'inactive')->count(),
                    'suspended_vehicles' => Vehicle::where('status', 'suspended')->count(),
                    'maintenance_vehicles' => Vehicle::where('status', 'maintenance')->count(),
                ],
                'verification' => [
                    'verified_vehicles' => Vehicle::where('verification_status', 'verified')->count(),
                    'pending_verification' => Vehicle::where('verification_status', 'pending')->count(),
                    'rejected_verification' => Vehicle::where('verification_status', 'rejected')->count(),
                ],
                'documents' => [
                    'expired_registrations' => Vehicle::whereDate('registration_expiry', '<', now())->count(),
                    'expired_insurance' => Vehicle::whereDate('insurance_expiry', '<', now())->count(),
                    'expiring_soon_registration' => Vehicle::whereBetween('registration_expiry', [now(), now()->addDays(30)])->count(),
                    'expiring_soon_insurance' => Vehicle::whereBetween('insurance_expiry', [now(), now()->addDays(30)])->count(),
                ],
                'activity' => [
                    'recent_registrations_7d' => Vehicle::where('created_at', '>=', now()->subDays(7))->count(),
                    'recent_registrations_30d' => Vehicle::where('created_at', '>=', now()->subDays(30))->count(),
                    'updated_recently' => Vehicle::where('updated_at', '>=', now()->subDays(7))->count(),
                ],
                'performance' => [
                    'avg_completion_percentage' => $this->getAverageCompletionPercentage(),
                    'vehicles_with_rides' => $this->getVehiclesWithRides(),
                    'most_active_vehicles' => $this->getMostActiveVehicles(5),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting detailed vehicle statistics: ' . $e->getMessage());
            return $this->getDefaultDetailedStatistics();
        }
    }

    /**
     * Get vehicle status distribution
     */
    public function getVehicleStatusDistribution()
    {
        try {
            return Vehicle::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [ucfirst($item->status) => $item->count];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting vehicle status distribution: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get vehicle type distribution
     */
    public function getVehicleTypeDistribution()
    {
        try {
            return Vehicle::selectRaw('vehicle_type, COUNT(*) as count')
                ->whereNotNull('vehicle_type')
                ->groupBy('vehicle_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [ucfirst($item->vehicle_type) => $item->count];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting vehicle type distribution: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get monthly vehicle registrations
     */
    public function getMonthlyVehicleRegistrations($months = 12)
    {
        try {
            $startDate = now()->subMonths($months);

            return Vehicle::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [Carbon::createFromFormat('Y-m', $item->month)->format('M Y') => $item->count];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting monthly vehicle registrations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top performing vehicles
     */
    public function getTopPerformingVehicles($limit = 10)
    {
        try {
            // This would typically join with rides table/collection
            // For now, return sample data based on vehicle info
            return Vehicle::with('driver')
                ->take($limit)
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'id' => $vehicle->id,
                        'vehicle_info' => $vehicle->full_name,
                        'license_plate' => $vehicle->license_plate,
                        'driver_name' => $vehicle->driver->name ?? 'Unknown',
                        'total_rides' => 0, // Would be calculated from rides
                        'completed_rides' => 0,
                        'total_earnings' => 0,
                        'completion_rate' => 0
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting top performing vehicles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get vehicles needing attention
     */
    public function getVehiclesNeedingAttention()
    {
        try {
            $issues = collect([]);

            // Expired registration
            $expiredRegistration = Vehicle::whereDate('registration_expiry', '<', now())
                ->with('driver')
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'type' => 'Expired Registration',
                        'vehicle' => $vehicle,
                        'issue' => 'Registration expired on ' . $vehicle->registration_expiry->format('M d, Y'),
                        'priority' => 'high'
                    ];
                });

            // Expired insurance
            $expiredInsurance = Vehicle::whereDate('insurance_expiry', '<', now())
                ->with('driver')
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'type' => 'Expired Insurance',
                        'vehicle' => $vehicle,
                        'issue' => 'Insurance expired on ' . $vehicle->insurance_expiry->format('M d, Y'),
                        'priority' => 'high'
                    ];
                });

            // Expiring soon
            $expiringSoon = Vehicle::where(function ($query) {
                $query->whereBetween('registration_expiry', [now(), now()->addDays(30)])
                    ->orWhereBetween('insurance_expiry', [now(), now()->addDays(30)]);
            })
                ->with('driver')
                ->get()
                ->map(function ($vehicle) {
                    $issues = [];
                    if ($vehicle->registration_expiry && $vehicle->registration_expiry->between(now(), now()->addDays(30))) {
                        $issues[] = 'Registration expires on ' . $vehicle->registration_expiry->format('M d, Y');
                    }
                    if ($vehicle->insurance_expiry && $vehicle->insurance_expiry->between(now(), now()->addDays(30))) {
                        $issues[] = 'Insurance expires on ' . $vehicle->insurance_expiry->format('M d, Y');
                    }

                    return [
                        'type' => 'Expiring Soon',
                        'vehicle' => $vehicle,
                        'issue' => implode(', ', $issues),
                        'priority' => 'medium'
                    ];
                });

            // Pending verification
            $pendingVerification = Vehicle::where('verification_status', 'pending')
                ->with('driver')
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'type' => 'Pending Verification',
                        'vehicle' => $vehicle,
                        'issue' => 'Vehicle verification pending',
                        'priority' => 'medium'
                    ];
                });

            return $issues->concat($expiredRegistration)
                ->concat($expiredInsurance)
                ->concat($expiringSoon)
                ->concat($pendingVerification)
                ->sortBy('priority')
                ->take(20)
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting vehicles needing attention: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get vehicle completion status
     */
    public function getVehicleCompletionStatus($vehicleId)
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            return 0;
        }

        try {
            $requiredFields = [
                'make',
                'model',
                'year',
                'color',
                'license_plate',
                'vehicle_type',
                'registration_number',
                'registration_expiry',
                'insurance_provider',
                'insurance_policy_number',
                'insurance_expiry'
            ];

            $completedFields = 0;
            foreach ($requiredFields as $field) {
                if (!empty($vehicle->$field)) {
                    $completedFields++;
                }
            }

            return round(($completedFields / count($requiredFields)) * 100);
        } catch (\Exception $e) {
            Log::error('Error calculating vehicle completion status: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get vehicle ride statistics
     */
    public function getVehicleRideStatistics($vehicleId)
    {
        try {
            // This would typically query the rides collection/table
            // For now, return default values
            return [
                'total_rides' => 0,
                'completed_rides' => 0,
                'cancelled_rides' => 0,
                'completion_rate' => 0,
                'total_distance' => 0,
                'total_earnings' => 0
            ];
        } catch (\Exception $e) {
            Log::error('Error getting vehicle ride statistics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get vehicle performance metrics
     */
    public function getVehiclePerformanceMetrics($vehicleId)
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            return null;
        }

        try {
            return [
                'rides_last_30_days' => 0,
                'earnings_last_30_days' => 0,
                'average_ride_duration' => 0,
                'average_ride_distance' => 0,
                'fuel_efficiency' => 0,
                'condition_rating' => $vehicle->condition_rating ?? 0,
                'last_maintenance' => $vehicle->last_maintenance_date ?? null
            ];
        } catch (\Exception $e) {
            Log::error('Error getting vehicle performance metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Perform bulk action on vehicles
     */
    public function performBulkAction($action, array $vehicleIds)
    {
        $processed = 0;
        $failed = 0;

        foreach ($vehicleIds as $vehicleId) {
            try {
                $vehicle = $this->getVehicleById($vehicleId);

                if (!$vehicle) {
                    $failed++;
                    continue;
                }

                switch ($action) {
                    case 'activate':
                        $success = $this->updateVehicleStatus($vehicleId, 'active');
                        break;
                    case 'deactivate':
                        $success = $this->updateVehicleStatus($vehicleId, 'inactive');
                        break;
                    case 'suspend':
                        $success = $this->updateVehicleStatus($vehicleId, 'suspended');
                        break;
                    case 'verify':
                        $success = $this->updateVehicleVerificationStatus($vehicleId, 'verified');
                        break;
                    case 'delete':
                        $success = $this->deleteVehicle($vehicleId);
                        break;
                    default:
                        $failed++;
                        continue 2;
                }

                if ($success) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("Bulk action failed for vehicle {$vehicleId}: " . $e->getMessage());
                $failed++;
            }
        }

        return [
            'success' => true,
            'processed_count' => $processed,
            'failed_count' => $failed
        ];
    }

    /**
     * Export vehicles data
     */
    public function exportVehicles(array $filters = [])
    {
        try {
            $query = Vehicle::with('driver');

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['verification_status'])) {
                $query->where('verification_status', $filters['verification_status']);
            }

            if (!empty($filters['vehicle_type'])) {
                $query->where('vehicle_type', $filters['vehicle_type']);
            }

            if (!empty($filters['created_from'])) {
                $query->where('created_at', '>=', $filters['created_from']);
            }

            if (!empty($filters['created_to'])) {
                $query->where('created_at', '<=', $filters['created_to']);
            }

            return $query->get()->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'driver_name' => $vehicle->driver->name ?? 'N/A',
                    'driver_email' => $vehicle->driver->email ?? 'N/A',
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'color' => $vehicle->color,
                    'license_plate' => $vehicle->license_plate,
                    'vin' => $vehicle->vin,
                    'vehicle_type' => $vehicle->vehicle_type,
                    'fuel_type' => $vehicle->fuel_type,
                    'status' => $vehicle->status,
                    'verification_status' => $vehicle->verification_status,
                    'is_primary' => $vehicle->is_primary ? 'Yes' : 'No',
                    'registration_expiry' => $vehicle->registration_expiry,
                    'insurance_expiry' => $vehicle->insurance_expiry,
                    'created_at' => $vehicle->created_at
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error exporting vehicles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total vehicles count
     */
    public function getTotalVehiclesCount()
    {
        try {
            return Vehicle::count();
        } catch (\Exception $e) {
            Log::error('Error getting total vehicles count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear vehicle cache
     */
    public function clearVehicleCache($vehicleId)
    {
        $cacheKeys = [
            "vehicle_details_{$vehicleId}",
            "vehicle_secondary_{$vehicleId}",
            "vehicle_dynamic_{$vehicleId}",
            "vehicle_recent_rides_{$vehicleId}",
            "vehicle_all_rides_{$vehicleId}",
            "vehicle_all_activities_{$vehicleId}"
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear general vehicle caches
        Cache::forget('vehicle_statistics');
        Cache::forget('vehicle_detailed_statistics');
        Cache::forget('vehicle_status_distribution');
        Cache::forget('vehicle_type_distribution');
        Cache::forget('vehicle_monthly_registrations');
        Cache::forget('top_performing_vehicles');
        Cache::forget('total_vehicles_count');
    }

    // ===================== PRIVATE HELPER METHODS =====================

    /**
     * Sync vehicle to Firebase
     */
    private function syncVehicleToFirebase($vehicle)
    {
        try {
            if (method_exists($vehicle, 'toFirebaseArray')) {
                $data = $vehicle->toFirebaseArray();
                $this->firestoreService
                    ->collection('vehicles')
                    ->create($data, $vehicle->id);

                $vehicle->update([
                    'firebase_synced' => true,
                    'firebase_synced_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing vehicle to Firebase: ' . $e->getMessage(), [
                'vehicle_id' => $vehicle->id
            ]);
        }
    }

    /**
     * Delete vehicle from Firebase
     */
    private function deleteVehicleFromFirebase($vehicleId)
    {
        try {
            $this->firestoreService
                ->collection('vehicles')
                ->delete($vehicleId);
        } catch (\Exception $e) {
            Log::error('Error deleting vehicle from Firebase: ' . $e->getMessage(), [
                'vehicle_id' => $vehicleId
            ]);
        }
    }

    /**
     * Create vehicle activity
     */
    private function createVehicleActivity($driverFirebaseUid, $type, $data)
    {
        try {
            $activity = array_merge($data, [
                'driver_firebase_uid' => $driverFirebaseUid,
                'activity_type' => $type,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'created_by' => session('firebase_user.uid', 'system')
            ]);

            $this->firestoreService
                ->collection('vehicle_activities')
                ->create($activity);
        } catch (\Exception $e) {
            Log::error('Error creating vehicle activity: ' . $e->getMessage());
        }
    }

    /**
     * Get average completion percentage
     */
    private function getAverageCompletionPercentage()
    {
        try {
            $vehicles = Vehicle::all();
            if ($vehicles->isEmpty()) {
                return 0;
            }

            $totalPercentage = $vehicles->sum(function ($vehicle) {
                return $this->getVehicleCompletionStatus($vehicle->id);
            });

            return round($totalPercentage / $vehicles->count(), 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get number of vehicles with rides
     */
    private function getVehiclesWithRides()
    {
        try {
            // This would typically check rides table/collection
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get most active vehicles
     */
    private function getMostActiveVehicles($limit = 5)
    {
        try {
            return Vehicle::take($limit)
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'vehicle_info' => $vehicle->full_name,
                        'license_plate' => $vehicle->license_plate,
                        'rides_count' => 0 // Would be calculated from rides
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get default statistics
     */
    private function getDefaultStatistics()
    {
        return [
            'total_vehicles' => 0,
            'active_vehicles' => 0,
            'verified_vehicles' => 0,
            'pending_verification' => 0,
            'maintenance_vehicles' => 0,
            'suspended_vehicles' => 0,
            'recent_registrations' => 0,
            'expired_registrations' => 0,
            'expired_insurance' => 0,
            'expiring_soon_registration' => 0,
            'expiring_soon_insurance' => 0,
            'vehicle_types_breakdown' => [],
            'fuel_types_breakdown' => []
        ];
    }

    /**
     * Get default detailed statistics
     */
    private function getDefaultDetailedStatistics()
    {
        return [
            'overview' => [
                'total_vehicles' => 0,
                'active_vehicles' => 0,
                'inactive_vehicles' => 0,
                'suspended_vehicles' => 0,
                'maintenance_vehicles' => 0,
            ],
            'verification' => [
                'verified_vehicles' => 0,
                'pending_verification' => 0,
                'rejected_verification' => 0,
            ],
            'documents' => [
                'expired_registrations' => 0,
                'expired_insurance' => 0,
                'expiring_soon_registration' => 0,
                'expiring_soon_insurance' => 0,
            ],
            'activity' => [
                'recent_registrations_7d' => 0,
                'recent_registrations_30d' => 0,
                'updated_recently' => 0,
            ],
            'performance' => [
                'avg_completion_percentage' => 0,
                'vehicles_with_rides' => 0,
                'most_active_vehicles' => [],
            ]
        ];
    }
}
