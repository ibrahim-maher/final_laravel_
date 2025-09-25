<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\DriverActivity;
use App\Modules\Driver\Models\Ride;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DriverVehicleService
{
    /**
     * Get all vehicles with filters
     */
    public function getAllVehicles(array $filters = [])
    {
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
    }

    /**
     * Get vehicle by ID
     */
    public function getVehicleById($vehicleId)
    {
        return Vehicle::with('driver')->find($vehicleId);
    }

    /**
     * Get vehicles by driver
     */
    public function getDriverVehicles($driverFirebaseUid)
    {
        return Vehicle::where('driver_firebase_uid', $driverFirebaseUid)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create new vehicle
     */
    public function createVehicle(array $data)
    {
        DB::beginTransaction();
        try {
            // If this is set as primary, unset other primary vehicles for this driver
            if (!empty($data['is_primary'])) {
                Vehicle::where('driver_firebase_uid', $data['driver_firebase_uid'])
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $vehicle = Vehicle::create($data);

            // Create activity for the driver
            DriverActivity::createActivity($data['driver_firebase_uid'], DriverActivity::TYPE_VEHICLE_UPDATE, [
                'title' => 'Vehicle Added',
                'description' => "New vehicle added: {$vehicle->full_name}",
                'metadata' => [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_info' => $vehicle->full_name
                ]
            ]);

            DB::commit();
            return $vehicle;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating vehicle: ' . $e->getMessage());
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

            $vehicle->update($data);

            // Create activity
            DriverActivity::createActivity($vehicle->driver_firebase_uid, DriverActivity::TYPE_VEHICLE_UPDATE, [
                'title' => 'Vehicle Updated',
                'description' => "Vehicle information updated: {$vehicle->full_name}",
                'metadata' => [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_info' => $vehicle->full_name
                ]
            ]);

            // Clear cache
            $this->clearVehicleCache($vehicleId);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating vehicle: ' . $e->getMessage());
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
            return false;
        }

        $vehicle->update(['status' => $status]);

        // Create activity
        DriverActivity::createActivity($vehicle->driver_firebase_uid, DriverActivity::TYPE_STATUS_CHANGE, [
            'title' => 'Vehicle Status Changed',
            'description' => "Vehicle status changed to: " . ucfirst($status),
            'metadata' => [
                'vehicle_id' => $vehicle->id,
                'vehicle_info' => $vehicle->full_name,
                'new_status' => $status
            ]
        ]);

        // Clear cache
        $this->clearVehicleCache($vehicleId);

        return true;
    }

    /**
     * Update vehicle verification status
     */
    public function updateVehicleVerificationStatus($vehicleId, $status, $adminUid = null, $notes = null)
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            return false;
        }

        $updateData = [
            'verification_status' => $status,
            'verification_date' => $status === 'verified' ? now() : null,
            'verified_by' => $adminUid
        ];

        if ($notes) {
            $updateData['verification_notes'] = $notes;
        }

        $vehicle->update($updateData);

        // Create activity
        DriverActivity::createActivity($vehicle->driver_firebase_uid, DriverActivity::TYPE_VERIFICATION_UPDATE, [
            'title' => 'Vehicle Verification Updated',
            'description' => "Vehicle verification status changed to: " . ucfirst($status),
            'metadata' => [
                'vehicle_id' => $vehicle->id,
                'vehicle_info' => $vehicle->full_name,
                'new_status' => $status,
                'admin_uid' => $adminUid,
                'notes' => $notes
            ]
        ]);

        // Clear cache
        $this->clearVehicleCache($vehicleId);

        return true;
    }

    /**
     * Delete vehicle
     */
    public function deleteVehicle($vehicleId)
    {
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$vehicle) {
            return false;
        }

        // Create activity before deletion
        DriverActivity::createActivity($vehicle->driver_firebase_uid, DriverActivity::TYPE_VEHICLE_UPDATE, [
            'title' => 'Vehicle Removed',
            'description' => "Vehicle removed: {$vehicle->full_name}",
            'metadata' => [
                'vehicle_id' => $vehicle->id,
                'vehicle_info' => $vehicle->full_name
            ]
        ]);

        // Clear cache
        $this->clearVehicleCache($vehicleId);

        return $vehicle->delete();
    }

    /**
     * Get vehicle documents
     */
    public function getVehicleDocuments($vehicleId)
    {
        return DriverDocument::where('vehicle_id', $vehicleId)->get();
    }

    /**
     * Get vehicle rides
     */
    public function getVehicleRides($vehicleId, array $filters = [])
    {
        $query = Ride::where('vehicle_id', $vehicleId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $limit = $filters['limit'] ?? 20;

        return $query->orderBy('created_at', 'desc')->paginate($limit);
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

        $query = DriverActivity::where('driver_firebase_uid', $vehicle->driver_firebase_uid)
            ->where(function ($q) use ($vehicleId) {
                $q->where('metadata->vehicle_id', $vehicleId)
                    ->orWhere('activity_type', 'vehicle_update');
            });

        if (!empty($filters['activity_type'])) {
            $query->where('activity_type', $filters['activity_type']);
        }

        $limit = $filters['limit'] ?? 30;

        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get vehicle maintenance records
     */
    public function getVehicleMaintenanceRecords($vehicleId)
    {
        // This would typically come from a maintenance_records table
        // For now, return empty collection
        return collect([]);
    }

    /**
     * Get vehicle statistics (basic)
     */
    public function getVehicleStatistics()
    {
        return [
            'total_vehicles' => Vehicle::count(),
            'active_vehicles' => Vehicle::where('status', Vehicle::STATUS_ACTIVE)->count(),
            'verified_vehicles' => Vehicle::where('verification_status', Vehicle::VERIFICATION_VERIFIED)->count(),
            'pending_verification' => Vehicle::where('verification_status', Vehicle::VERIFICATION_PENDING)->count(),
            'maintenance_vehicles' => Vehicle::where('status', Vehicle::STATUS_MAINTENANCE)->count(),
            'suspended_vehicles' => Vehicle::where('status', Vehicle::STATUS_SUSPENDED)->count(),
            'recent_registrations' => Vehicle::where('created_at', '>=', now()->subDays(7))->count(),
            'expired_registrations' => Vehicle::where('registration_expiry', '<', now())->count(),
            'expired_insurance' => Vehicle::where('insurance_expiry', '<', now())->count(),
            'expiring_soon_registration' => Vehicle::whereBetween('registration_expiry', [now(), now()->addDays(30)])->count(),
            'expiring_soon_insurance' => Vehicle::whereBetween('insurance_expiry', [now(), now()->addDays(30)])->count(),
            'vehicle_types_breakdown' => Vehicle::selectRaw('vehicle_type, COUNT(*) as count')
                ->groupBy('vehicle_type')
                ->pluck('count', 'vehicle_type')
                ->toArray(),
            'fuel_types_breakdown' => Vehicle::selectRaw('fuel_type, COUNT(*) as count')
                ->groupBy('fuel_type')
                ->pluck('count', 'fuel_type')
                ->toArray()
        ];
    }

    /**
     * Get detailed vehicle statistics (for admin dashboard)
     */
    public function getDetailedVehicleStatistics()
    {
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
                'expired_registrations' => Vehicle::where('registration_expiry', '<', now())->count(),
                'expired_insurance' => Vehicle::where('insurance_expiry', '<', now())->count(),
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
    }

    /**
     * Get vehicle status distribution
     */
    public function getVehicleStatusDistribution()
    {
        return Vehicle::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [ucfirst($item->status) => $item->count];
            })
            ->toArray();
    }

    /**
     * Get vehicle type distribution
     */
    public function getVehicleTypeDistribution()
    {
        return Vehicle::selectRaw('vehicle_type, COUNT(*) as count')
            ->whereNotNull('vehicle_type')
            ->groupBy('vehicle_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [ucfirst($item->vehicle_type) => $item->count];
            })
            ->toArray();
    }

    /**
     * Get monthly vehicle registrations
     */
    public function getMonthlyVehicleRegistrations($months = 12)
    {
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
    }

    /**
     * Get top performing vehicles
     */
    public function getTopPerformingVehicles($limit = 10)
    {
        return Vehicle::with(['driver'])
            ->leftJoin('rides', 'vehicles.id', '=', 'rides.vehicle_id')
            ->selectRaw('vehicles.*, 
                                 COUNT(rides.id) as total_rides,
                                 SUM(CASE WHEN rides.status = "completed" THEN 1 ELSE 0 END) as completed_rides,
                                 SUM(CASE WHEN rides.status = "completed" THEN rides.driver_earnings ELSE 0 END) as total_earnings')
            ->groupBy('vehicles.id')
            ->orderByDesc('total_earnings')
            ->limit($limit)
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'vehicle_info' => "{$vehicle->year} {$vehicle->make} {$vehicle->model}",
                    'license_plate' => $vehicle->license_plate,
                    'driver_name' => $vehicle->driver->name ?? 'Unknown',
                    'total_rides' => $vehicle->total_rides ?? 0,
                    'completed_rides' => $vehicle->completed_rides ?? 0,
                    'total_earnings' => $vehicle->total_earnings ?? 0,
                    'completion_rate' => $vehicle->total_rides > 0
                        ? round(($vehicle->completed_rides / $vehicle->total_rides) * 100, 1)
                        : 0
                ];
            })
            ->toArray();
    }

    /**
     * Get vehicles needing attention
     */
    public function getVehiclesNeedingAttention()
    {
        $expiredRegistration = Vehicle::where('registration_expiry', '<', now())
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

        $expiredInsurance = Vehicle::where('insurance_expiry', '<', now())
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

        $expiringSoon = Vehicle::whereBetween('registration_expiry', [now(), now()->addDays(30)])
            ->orWhereBetween('insurance_expiry', [now(), now()->addDays(30)])
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

        return $expiredRegistration->concat($expiredInsurance)
            ->concat($expiringSoon)
            ->concat($pendingVerification)
            ->sortBy('priority')
            ->take(20)
            ->values()
            ->toArray();
    }

    /**
     * Get average completion percentage
     */
    private function getAverageCompletionPercentage()
    {
        $vehicles = Vehicle::all();
        if ($vehicles->isEmpty()) {
            return 0;
        }

        $totalPercentage = $vehicles->sum(function ($vehicle) {
            return $this->getVehicleCompletionStatus($vehicle->id);
        });

        return round($totalPercentage / $vehicles->count(), 1);
    }

    /**
     * Get number of vehicles with rides
     */
    private function getVehiclesWithRides()
    {
        return Vehicle::whereHas('rides')->count();
    }

    /**
     * Get most active vehicles
     */
    private function getMostActiveVehicles($limit = 5)
    {
        return Vehicle::withCount(['rides' => function ($query) {
            $query->where('created_at', '>=', now()->subDays(30));
        }])
            ->orderByDesc('rides_count')
            ->limit($limit)
            ->get()
            ->map(function ($vehicle) {
                return [
                    'vehicle_info' => "{$vehicle->year} {$vehicle->make} {$vehicle->model}",
                    'license_plate' => $vehicle->license_plate,
                    'rides_count' => $vehicle->rides_count
                ];
            })
            ->toArray();
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
    }

    /**
     * Get vehicle ride statistics
     */
    public function getVehicleRideStatistics($vehicleId)
    {
        $totalRides = Ride::where('vehicle_id', $vehicleId)->count();
        $completedRides = Ride::where('vehicle_id', $vehicleId)->where('status', 'completed')->count();
        $cancelledRides = Ride::where('vehicle_id', $vehicleId)->where('status', 'cancelled')->count();

        return [
            'total_rides' => $totalRides,
            'completed_rides' => $completedRides,
            'cancelled_rides' => $cancelledRides,
            'completion_rate' => $totalRides > 0 ? round(($completedRides / $totalRides) * 100, 2) : 0,
            'total_distance' => Ride::where('vehicle_id', $vehicleId)
                ->where('status', 'completed')
                ->sum('distance_km') ?? 0,
            'total_earnings' => Ride::where('vehicle_id', $vehicleId)
                ->where('status', 'completed')
                ->sum('driver_earnings') ?? 0
        ];
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

        $last30Days = now()->subDays(30);

        return [
            'rides_last_30_days' => Ride::where('vehicle_id', $vehicleId)
                ->where('created_at', '>=', $last30Days)
                ->count(),
            'earnings_last_30_days' => Ride::where('vehicle_id', $vehicleId)
                ->where('created_at', '>=', $last30Days)
                ->where('status', 'completed')
                ->sum('driver_earnings') ?? 0,
            'average_ride_duration' => Ride::where('vehicle_id', $vehicleId)
                ->where('status', 'completed')
                ->avg('duration_minutes') ?? 0,
            'average_ride_distance' => Ride::where('vehicle_id', $vehicleId)
                ->where('status', 'completed')
                ->avg('distance_km') ?? 0,
            'fuel_efficiency' => $this->calculateFuelEfficiency($vehicleId),
            'condition_rating' => $vehicle->condition_rating ?? 0,
            'last_maintenance' => $vehicle->last_maintenance_date ?? null
        ];
    }

    /**
     * Calculate fuel efficiency (placeholder)
     */
    private function calculateFuelEfficiency($vehicleId)
    {
        // This would calculate based on fuel consumption vs distance
        // For now, return a placeholder value
        return 0;
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
                        $vehicle->update(['status' => 'active']);
                        break;
                    case 'deactivate':
                        $vehicle->update(['status' => 'inactive']);
                        break;
                    case 'suspend':
                        $vehicle->update(['status' => 'suspended']);
                        break;
                    case 'verify':
                        $vehicle->update(['verification_status' => 'verified']);
                        break;
                    case 'delete':
                        $vehicle->delete();
                        break;
                    default:
                        $failed++;
                        continue 2;
                }

                // Clear cache for each vehicle
                $this->clearVehicleCache($vehicleId);
                $processed++;
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
    }

    /**
     * Get vehicles by verification status
     */
    public function getVehiclesByVerificationStatus($status)
    {
        return Vehicle::where('verification_status', $status)->with('driver')->get();
    }

    /**
     * Get vehicles by status
     */
    public function getVehiclesByStatus($status)
    {
        return Vehicle::where('status', $status)->with('driver')->get();
    }

    /**
     * Get expired registrations
     */
    public function getExpiredRegistrations()
    {
        return Vehicle::where('registration_expiry', '<', now())->with('driver')->get();
    }

    /**
     * Get expired insurance
     */
    public function getExpiredInsurance()
    {
        return Vehicle::where('insurance_expiry', '<', now())->with('driver')->get();
    }

    /**
     * Get registrations expiring soon
     */
    public function getRegistrationsExpiringSoon($days = 30)
    {
        return Vehicle::whereBetween('registration_expiry', [now(), now()->addDays($days)])
            ->with('driver')
            ->get();
    }

    /**
     * Get insurance expiring soon
     */
    public function getInsuranceExpiringSoon($days = 30)
    {
        return Vehicle::whereBetween('insurance_expiry', [now(), now()->addDays($days)])
            ->with('driver')
            ->get();
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
            cache()->forget($key);
        }

        // Clear general vehicle caches
        cache()->forget('vehicle_statistics');
        cache()->forget('vehicle_detailed_statistics');
        cache()->forget('vehicle_status_distribution');
        cache()->forget('vehicle_type_distribution');
        cache()->forget('vehicle_monthly_registrations');
        cache()->forget('top_performing_vehicles');
        cache()->forget('total_vehicles_count');
    }

    /**
     * Get total vehicles count
     */
    public function getTotalVehiclesCount()
    {
        return Vehicle::count();
    }

    /**
     * Bulk import vehicles
     */
    public function bulkImportVehicles(array $vehiclesData)
    {
        $imported = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($vehiclesData as $vehicleData) {
                try {
                    if (empty($vehicleData['license_plate']) || empty($vehicleData['driver_firebase_uid'])) {
                        $failed++;
                        continue;
                    }

                    // Check if vehicle already exists
                    if (Vehicle::where('license_plate', $vehicleData['license_plate'])->exists()) {
                        $failed++;
                        continue;
                    }

                    // Verify driver exists
                    if (!Driver::where('firebase_uid', $vehicleData['driver_firebase_uid'])->exists()) {
                        $failed++;
                        continue;
                    }

                    Vehicle::create($vehicleData);
                    $imported++;
                } catch (\Exception $e) {
                    Log::error('Import vehicle failed: ' . $e->getMessage());
                    $failed++;
                }
            }

            DB::commit();
            return [
                'success' => true,
                'imported_count' => $imported,
                'failed_count' => $failed
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk import failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Bulk import failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sync Firebase vehicles
     */
    public function syncFirebaseVehicles()
    {
        // This would typically sync with Firebase
        // Implementation depends on your Firebase setup
        return [
            'success' => true,
            'synced_count' => 0,
            'message' => 'No new vehicles to sync'
        ];
    }

    /**
     * Get vehicle dashboard data for admin
     */
    public function getVehicleDashboardData()
    {
        return [
            'statistics' => $this->getVehicleStatistics(),
            'recent_vehicles' => Vehicle::with('driver')->latest()->limit(10)->get(),
            'pending_verifications' => Vehicle::where('verification_status', 'pending')
                ->with('driver')
                ->limit(10)
                ->get(),
            'expired_registrations' => $this->getExpiredRegistrations(),
            'expired_insurance' => $this->getExpiredInsurance(),
            'expiring_soon_registration' => $this->getRegistrationsExpiringSoon(15),
            'expiring_soon_insurance' => $this->getInsuranceExpiringSoon(15)
        ];
    }
}
