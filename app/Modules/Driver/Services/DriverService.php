<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\DriverActivity;
use App\Modules\Driver\Models\Ride;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverService
{
    /**
     * Get recent activities for a specific driver
     */
    public function getRecentActivitiesForDriver($firebaseUid, $days = 7, $limit = 10)
    {
        return DriverActivity::where('driver_firebase_uid', $firebaseUid)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get active rides for a specific driver - FIXED
     */
    public function getActiveRidesForDriver($firebaseUid)
    {
        try {
            $driver = $this->getDriverById($firebaseUid);
            if (!$driver) {
                return [];
            }

            return $driver->getActiveRides();
        } catch (\Exception $e) {
            Log::error('Error getting active rides: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get admin dashboard data - FIXED VERSION
     */
    public function getAdminDashboardData()
    {
        try {
            $statistics = $this->getDriverStatistics();
            $systemAnalytics = $this->getSystemAnalytics();

            // Get recent drivers with error handling
            $recentDrivers = Driver::latest()->limit(10)->get()->map(function ($driver) {
                return [
                    'firebase_uid' => $driver->firebase_uid,
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'status' => $driver->status,
                    'verification_status' => $driver->verification_status,
                    'created_at' => $driver->created_at,
                    'join_date' => $driver->join_date ?? $driver->created_at
                ];
            })->toArray();

            // Get recent activities with driver info
            $recentActivities = DriverActivity::with('driver')
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'title' => $activity->title,
                        'description' => $activity->description,
                        'activity_type' => $activity->activity_type,
                        'created_at' => $activity->created_at,
                        'driver_name' => $activity->driver->name ?? 'Unknown Driver',
                        'driver_firebase_uid' => $activity->driver_firebase_uid
                    ];
                })
                ->toArray();

            // Get pending verifications
            $pendingVerifications = Driver::where('verification_status', Driver::VERIFICATION_PENDING)
                ->limit(10)
                ->get()
                ->map(function ($driver) {
                    return [
                        'firebase_uid' => $driver->firebase_uid,
                        'name' => $driver->name,
                        'email' => $driver->email,
                        'created_at' => $driver->created_at,
                        'verification_status' => $driver->verification_status
                    ];
                })
                ->toArray();

            // Get expiring documents with driver info
            $expiringDocuments = DriverDocument::whereBetween('expiry_date', [now(), now()->addDays(30)])
                ->with('driver')
                ->limit(10)
                ->get()
                ->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'document_type' => $document->document_type,
                        'document_name' => $document->document_name,
                        'expiry_date' => $document->expiry_date,
                        'driver_name' => $document->driver->name ?? 'Unknown Driver',
                        'driver_firebase_uid' => $document->driver_firebase_uid
                    ];
                })
                ->toArray();

            return [
                'statistics' => $statistics,
                'system_analytics' => $systemAnalytics,
                'recent_drivers' => $recentDrivers,
                'recent_activities' => $recentActivities,
                'pending_verifications' => $pendingVerifications,
                'expiring_documents' => $expiringDocuments
            ];
        } catch (\Exception $e) {
            Log::error('Error in getAdminDashboardData: ' . $e->getMessage());

            // Return minimal safe data structure if error occurs
            return [
                'statistics' => [
                    'total_drivers' => 0,
                    'active_drivers' => 0,
                    'verified_drivers' => 0,
                    'pending_verification' => 0,
                    'available_drivers' => 0,
                    'recent_registrations' => 0,
                    'total_rides' => 0,
                    'completed_rides' => 0,
                    'total_earnings' => 0,
                    'average_rating' => 0
                ],
                'system_analytics' => [
                    'driver_statistics' => [],
                    'activity_statistics' => [],
                    'document_statistics' => [],
                    'vehicle_statistics' => [],
                    'ride_statistics' => []
                ],
                'recent_drivers' => [],
                'recent_activities' => [],
                'pending_verifications' => [],
                'expiring_documents' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all drivers with filters
     */
    public function getAllDrivers(array $filters = [])
    {
        $query = Driver::query();

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('license_number', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        if (!empty($filters['availability_status'])) {
            $query->where('availability_status', $filters['availability_status']);
        }

        $limit = $filters['limit'] ?? 50;

        return $query->paginate($limit);
    }

    /**
     * Get driver by Firebase UID
     */
    public function getDriverById($firebaseUid)
    {
        return Driver::where('firebase_uid', $firebaseUid)->first();
    }

    /**
     * Create new driver
     */
    public function createDriver(array $data)
    {
        DB::beginTransaction();
        try {
            $driver = Driver::create($data);

            // Create welcome activity if DriverActivity model exists
            try {
                DriverActivity::createActivity($driver->firebase_uid, DriverActivity::TYPE_PROFILE_UPDATE, [
                    'title' => 'Welcome to the Platform',
                    'description' => 'Your driver profile has been created'
                ]);
            } catch (\Exception $e) {
                Log::warning('Could not create welcome activity: ' . $e->getMessage());
            }

            DB::commit();
            return $driver;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating driver: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update driver
     */
    public function updateDriver($firebaseUid, array $data)
    {
        $driver = $this->getDriverById($firebaseUid);

        if (!$driver) {
            return false;
        }

        $driver->update($data);

        // Create activity if possible
        try {
            DriverActivity::createActivity($firebaseUid, DriverActivity::TYPE_PROFILE_UPDATE, [
                'title' => 'Profile Updated',
                'description' => 'Driver profile information has been updated'
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not create update activity: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Update driver status
     */
    public function updateDriverStatus($firebaseUid, $status)
    {
        $driver = $this->getDriverById($firebaseUid);

        if (!$driver) {
            return false;
        }

        $driver->update(['status' => $status]);

        // Create activity if possible
        try {
            DriverActivity::createActivity($firebaseUid, DriverActivity::TYPE_STATUS_CHANGE, [
                'title' => 'Status Changed',
                'description' => "Driver status changed to: " . ucfirst($status),
                'metadata' => ['new_status' => $status]
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not create status activity: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Delete driver
     */
    public function deleteDriver($firebaseUid)
    {
        $driver = $this->getDriverById($firebaseUid);

        if (!$driver) {
            return false;
        }

        return $driver->delete();
    }

    /**
     * Get driver vehicles
     */
    public function getDriverVehicles($firebaseUid)
    {
        try {
            return Vehicle::where('driver_firebase_uid', $firebaseUid)->get();
        } catch (\Exception $e) {
            Log::error('Error getting driver vehicles: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get driver documents
     */
    public function getDriverDocuments($firebaseUid)
    {
        try {
            return DriverDocument::where('driver_firebase_uid', $firebaseUid)->get();
        } catch (\Exception $e) {
            Log::error('Error getting driver documents: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get driver rides - FIXED to use custom method
     */
    public function getDriverRides($firebaseUid, array $filters = [])
    {
        try {
            $driver = $this->getDriverById($firebaseUid);
            if (!$driver) {
                return collect([]);
            }

            $rides = $driver->getRides($filters);
            return collect($rides);
        } catch (\Exception $e) {
            Log::error('Error getting driver rides: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get driver activities
     */
    public function getDriverActivities($firebaseUid, array $filters = [])
    {
        try {
            $query = DriverActivity::where('driver_firebase_uid', $firebaseUid);

            if (!empty($filters['activity_type'])) {
                $query->where('activity_type', $filters['activity_type']);
            }

            if (!empty($filters['unread_only'])) {
                $query->where('is_read', false);
            }

            $limit = $filters['limit'] ?? 30;

            return $query->orderBy('created_at', 'desc')->paginate($limit);
        } catch (\Exception $e) {
            Log::error('Error getting driver activities: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get driver statistics - FIXED VERSION
     */
    public function getDriverStatistics()
    {
        try {
            // Get basic driver statistics from Eloquent
            $statistics = [
                'total_drivers' => Driver::count(),
                'active_drivers' => Driver::where('status', Driver::STATUS_ACTIVE)->count(),
                'verified_drivers' => Driver::where('verification_status', Driver::VERIFICATION_VERIFIED)->count(),
                'available_drivers' => Driver::where('availability_status', Driver::AVAILABILITY_AVAILABLE)
                    ->where('status', Driver::STATUS_ACTIVE)
                    ->count(),
                'pending_verification' => Driver::where('verification_status', Driver::VERIFICATION_PENDING)->count(),
                'recent_registrations' => Driver::where('created_at', '>=', now()->subDays(7))->count(),
                'total_earnings' => Driver::sum('total_earnings') ?? 0,
                'average_rating' => round(Driver::where('rating', '>', 0)->avg('rating') ?? 0, 2)
            ];

            // Get ride statistics from Firestore
            try {
                $rideModel = new Ride();
                $rideStats = $rideModel->getRideStatistics();
                $statistics['total_rides'] = $rideStats['total_rides'] ?? 0;
                $statistics['completed_rides'] = $rideStats['completed_rides'] ?? 0;
            } catch (\Exception $e) {
                Log::warning('Error getting ride statistics from Firestore: ' . $e->getMessage());
                $statistics['total_rides'] = 0;
                $statistics['completed_rides'] = 0;
            }

            return $statistics;
        } catch (\Exception $e) {
            Log::error('Error in getDriverStatistics: ' . $e->getMessage());

            // Return safe defaults
            return [
                'total_drivers' => 0,
                'active_drivers' => 0,
                'verified_drivers' => 0,
                'available_drivers' => 0,
                'pending_verification' => 0,
                'recent_registrations' => 0,
                'total_rides' => 0,
                'completed_rides' => 0,
                'total_earnings' => 0,
                'average_rating' => 0
            ];
        }
    }

    /**
     * Get drivers near location
     */
    public function getDriversNearLocation($latitude, $longitude, $radius = 10)
    {
        try {
            return Driver::available()
                ->nearby($latitude, $longitude, $radius)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting drivers near location: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Update driver location
     */
    public function updateDriverLocation($firebaseUid, $latitude, $longitude, $address = null)
    {
        $driver = $this->getDriverById($firebaseUid);

        if (!$driver) {
            return false;
        }

        $driver->updateLocation($latitude, $longitude, $address);

        // Create low priority activity if possible
        try {
            DriverActivity::createActivity($firebaseUid, DriverActivity::TYPE_LOCATION_UPDATE, [
                'title' => 'Location Updated',
                'description' => 'Driver location has been updated',
                'location_latitude' => $latitude,
                'location_longitude' => $longitude,
                'location_address' => $address,
                'priority' => DriverActivity::PRIORITY_LOW
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not create location activity: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Perform bulk action on drivers
     */
    public function performBulkAction($action, array $driverIds)
    {
        $processed = 0;
        $failed = 0;

        foreach ($driverIds as $driverId) {
            try {
                $driver = $this->getDriverById($driverId);

                if (!$driver) {
                    $failed++;
                    continue;
                }

                switch ($action) {
                    case 'activate':
                        $driver->update(['status' => Driver::STATUS_ACTIVE]);
                        break;
                    case 'deactivate':
                        $driver->update(['status' => Driver::STATUS_INACTIVE]);
                        break;
                    case 'suspend':
                        $driver->update(['status' => Driver::STATUS_SUSPENDED]);
                        break;
                    case 'verify':
                        $driver->update(['verification_status' => Driver::VERIFICATION_VERIFIED]);
                        break;
                    case 'delete':
                        $driver->delete();
                        break;
                    default:
                        $failed++;
                        continue 2;
                }

                $processed++;
            } catch (\Exception $e) {
                Log::error("Bulk action failed for driver {$driverId}: " . $e->getMessage());
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
     * Get system analytics - FIXED VERSION
     */
    public function getSystemAnalytics()
    {
        try {
            $last30Days = now()->subDays(30);
            $last7Days = now()->subDays(7);

            $analytics = [
                'driver_statistics' => $this->getDriverStatistics()
            ];

            // Get activity statistics if DriverActivity exists
            try {
                $analytics['activity_statistics'] = [
                    'total_activities' => DriverActivity::count(),
                    'activities_today' => DriverActivity::whereDate('created_at', today())->count(),
                    'activities_this_week' => DriverActivity::where('created_at', '>=', $last7Days)->count(),
                    'activities_this_month' => DriverActivity::where('created_at', '>=', $last30Days)->count(),
                    'unread_activities' => DriverActivity::where('is_read', false)->count()
                ];
            } catch (\Exception $e) {
                Log::warning('Error getting activity statistics: ' . $e->getMessage());
                $analytics['activity_statistics'] = [];
            }

            // Get document statistics if DriverDocument exists
            try {
                $analytics['document_statistics'] = [
                    'total_documents' => DriverDocument::count(),
                    'pending_verification' => DriverDocument::where('verification_status', 'pending')->count(),
                    'expired_documents' => DriverDocument::where('expiry_date', '<', now())->count(),
                    'expiring_soon' => DriverDocument::whereBetween('expiry_date', [now(), now()->addDays(30)])->count()
                ];
            } catch (\Exception $e) {
                Log::warning('Error getting document statistics: ' . $e->getMessage());
                $analytics['document_statistics'] = [];
            }

            // Get vehicle statistics if Vehicle exists
            try {
                $analytics['vehicle_statistics'] = [
                    'total_vehicles' => Vehicle::count(),
                    'active_vehicles' => Vehicle::where('status', 'active')->count(),
                    'pending_verification' => Vehicle::where('verification_status', 'pending')->count()
                ];
            } catch (\Exception $e) {
                Log::warning('Error getting vehicle statistics: ' . $e->getMessage());
                $analytics['vehicle_statistics'] = [];
            }

            // Get ride statistics from Firestore
            try {
                $rideModel = new Ride();
                $analytics['ride_statistics'] = $rideModel->getRideStatistics();
            } catch (\Exception $e) {
                Log::warning('Error getting ride analytics: ' . $e->getMessage());
                $analytics['ride_statistics'] = [
                    'total_rides' => 0,
                    'completed_rides' => 0,
                    'cancelled_rides' => 0,
                    'in_progress_rides' => 0,
                    'pending_rides' => 0,
                    'total_earnings' => 0,
                    'average_rating' => 0
                ];
            }

            return $analytics;
        } catch (\Exception $e) {
            Log::error('Error in getSystemAnalytics: ' . $e->getMessage());

            // Return safe default analytics
            return [
                'driver_statistics' => [],
                'activity_statistics' => [],
                'document_statistics' => [],
                'vehicle_statistics' => [],
                'ride_statistics' => []
            ];
        }
    }

    /**
     * Get driver performance metrics - FIXED
     */
    public function getDriverPerformanceMetrics($firebaseUid)
    {
        $driver = $this->getDriverById($firebaseUid);

        if (!$driver) {
            return null;
        }

        try {
            // Get basic metrics from driver model
            $metrics = [
                'rating' => $driver->rating ?? 0,
                'total_rides' => $driver->total_rides ?? 0,
                'completed_rides' => $driver->completed_rides ?? 0,
                'cancelled_rides' => $driver->cancelled_rides ?? 0,
                'completion_rate' => $driver->completion_rate ?? 0,
                'cancellation_rate' => $driver->cancellation_rate ?? 0,
                'total_earnings' => $driver->total_earnings ?? 0
            ];

            // Get detailed metrics from Firestore rides
            try {
                $rideStats = $driver->getRideStatistics();
                $metrics = array_merge($metrics, $rideStats);

                // Get recent rides for additional metrics
                $recentRides = $driver->getRides(['limit' => 100]);
                $last30DaysRides = array_filter($recentRides, function ($ride) {
                    return isset($ride['created_at']) &&
                        strtotime($ride['created_at']) >= strtotime('-30 days');
                });

                $metrics['rides_last_30_days'] = count($last30DaysRides);
                $metrics['earnings_last_30_days'] = array_sum(array_column($last30DaysRides, 'actual_fare'));
            } catch (\Exception $e) {
                Log::warning('Error getting detailed ride metrics: ' . $e->getMessage());
                $metrics['rides_last_30_days'] = 0;
                $metrics['earnings_last_30_days'] = 0;
            }

            return $metrics;
        } catch (\Exception $e) {
            Log::error('Error getting driver performance metrics: ' . $e->getMessage());
            return null;
        }
    }

    // Additional helper methods

    public function getDriversByVerificationStatus($status)
    {
        return Driver::where('verification_status', $status)->get();
    }

    public function getDriversByStatus($status)
    {
        return Driver::where('status', $status)->get();
    }

    public function getPendingDocuments()
    {
        try {
            return DriverDocument::where('verification_status', 'pending')->get();
        } catch (\Exception $e) {
            Log::error('Error getting pending documents: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function getExpiredDocuments()
    {
        try {
            return DriverDocument::where('expiry_date', '<', now())->get();
        } catch (\Exception $e) {
            Log::error('Error getting expired documents: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function getDocumentsExpiringSoon()
    {
        try {
            return DriverDocument::whereBetween('expiry_date', [now(), now()->addDays(30)])->get();
        } catch (\Exception $e) {
            Log::error('Error getting expiring documents: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function updateDriverVerificationStatus($firebaseUid, $status, $adminUid = null, $notes = null)
    {
        $driver = $this->getDriverById($firebaseUid);

        if (!$driver) {
            return false;
        }

        $updateData = [
            'verification_status' => $status,
            'verified_at' => $status === 'verified' ? now() : null,
            'verified_by' => $adminUid
        ];

        if ($notes) {
            $updateData['verification_notes'] = $notes;
        }

        $driver->update($updateData);

        // Create activity if possible
        try {
            DriverActivity::createActivity($firebaseUid, DriverActivity::TYPE_VERIFICATION_UPDATE, [
                'title' => 'Verification Status Updated',
                'description' => "Verification status changed to: " . ucfirst($status),
                'metadata' => [
                    'new_status' => $status,
                    'admin_uid' => $adminUid,
                    'notes' => $notes
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not create verification activity: ' . $e->getMessage());
        }

        return true;
    }
    public function getDriverProfileCompletion($firebaseUid)
    {
        $driver = $this->getDriverById($firebaseUid);

        if (!$driver) {
            return 0;
        }

        $requiredFields = [
            'name',
            'email',
            'phone',
            'date_of_birth',
            'gender',
            'address',
            'city',
            'state',
            'license_number',
            'license_expiry'
        ];

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (!empty($driver->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($requiredFields)) * 100);
    }
    public function getDriverRideStatistics($firebaseUid)
    {
        $driver = $this->getDriverById($firebaseUid);

        if (!$driver) {
            return null;
        }

        return [
            'total_rides' => $driver->total_rides ?? 0,
            'completed_rides' => $driver->completed_rides ?? 0,
            'cancelled_rides' => $driver->cancelled_rides ?? 0,
            'completion_rate' => $driver->completion_rate ?? 0,
            'total_earnings' => $driver->total_earnings ?? 0,
            'average_rating' => $driver->rating ?? 0
        ];
    }


    public function clearDriverCache($firebaseUid)
    {
        $cacheKeys = [
            "driver_details_{$firebaseUid}",
            "driver_secondary_{$firebaseUid}",
            "driver_dynamic_{$firebaseUid}",
            "driver_recent_rides_{$firebaseUid}",
            "driver_all_rides_{$firebaseUid}",
            "driver_all_activities_{$firebaseUid}"
        ];

        foreach ($cacheKeys as $key) {
            cache()->forget($key);
        }

        // Clear general driver caches
        cache()->forget('driver_statistics');
        cache()->forget('total_drivers_count');
    }

    public function getTotalDriversCount()
    {
        return Driver::count();
    }
}
