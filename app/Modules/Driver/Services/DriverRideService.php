<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Ride;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DriverRideService
{
    protected $rideModel;

    public function __construct()
    {
        $this->rideModel = new Ride();
    }

    /**
     * Get all rides with comprehensive filtering
     */
    public function getAllRides(array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Getting all rides', ['filters' => $filters]);

            return $this->rideModel->getAllRides($filters);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting all rides: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get ride by ID
     */
    public function getRideById(string $rideId): ?array
    {
        try {
            Log::info('DriverRideService: Getting ride by ID', ['ride_id' => $rideId]);

            $cacheKey = "ride_{$rideId}";

            return Cache::remember($cacheKey, 300, function () use ($rideId) {
                return $this->rideModel->getRideById($rideId);
            });
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting ride by ID: ' . $e->getMessage(), [
                'ride_id' => $rideId
            ]);
            return null;
        }
    }

    /**
     * Get rides for a specific driver
     */
    public function getDriverRides(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Getting driver rides', [
                'driver_uid' => $driverFirebaseUid,
                'filters' => $filters
            ]);

            return $this->rideModel->getRidesByDriver($driverFirebaseUid, $filters);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting driver rides: ' . $e->getMessage(), [
                'driver_uid' => $driverFirebaseUid,
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Get rides for a specific passenger
     */
    public function getPassengerRides(string $passengerFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Getting passenger rides', [
                'passenger_uid' => $passengerFirebaseUid,
                'filters' => $filters
            ]);

            $filters['passenger_firebase_uid'] = $passengerFirebaseUid;
            return $this->rideModel->getAllRides($filters);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting passenger rides: ' . $e->getMessage(), [
                'passenger_uid' => $passengerFirebaseUid,
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Search rides with advanced filtering
     */
    public function searchRides(string $searchTerm, array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Searching rides', [
                'search_term' => $searchTerm,
                'filters' => $filters
            ]);

            return $this->rideModel->searchRides($searchTerm, $filters);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error searching rides: ' . $e->getMessage(), [
                'search_term' => $searchTerm,
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Create new ride with validation
     */
    public function createRide(array $rideData): ?array
    {
        try {
            Log::info('DriverRideService: Creating new ride', [
                'driver_uid' => $rideData['driver_firebase_uid'] ?? 'unknown',
                'passenger_name' => $rideData['passenger_name'] ?? 'unknown'
            ]);

            // Validate required fields
            $validation = $this->validateRideData($rideData);
            if (!$validation['valid']) {
                Log::error('DriverRideService: Validation failed for ride creation', [
                    'errors' => $validation['errors']
                ]);
                return null;
            }

            // Enrich ride data
            $rideData = $this->enrichRideData($rideData);

            $result = $this->rideModel->createRide($rideData);

            if ($result) {
                // Clear relevant caches
                $this->clearRidesCaches();

                Log::info('DriverRideService: Ride created successfully', [
                    'ride_id' => $result['id']
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error creating ride: ' . $e->getMessage(), [
                'data' => $rideData,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Update ride with validation
     */
    public function updateRide(string $rideId, array $rideData): bool
    {
        try {
            Log::info('DriverRideService: Updating ride', [
                'ride_id' => $rideId,
                'fields' => array_keys($rideData)
            ]);

            // Get current ride for validation
            $currentRide = $this->getRideById($rideId);
            if (!$currentRide) {
                Log::error('DriverRideService: Ride not found for update', ['ride_id' => $rideId]);
                return false;
            }

            // Validate update data
            $validation = $this->validateRideUpdate($rideData, $currentRide);
            if (!$validation['valid']) {
                Log::error('DriverRideService: Validation failed for ride update', [
                    'ride_id' => $rideId,
                    'errors' => $validation['errors']
                ]);
                return false;
            }

            $result = $this->rideModel->updateRide($rideId, $rideData);

            if ($result) {
                // Clear relevant caches
                $this->clearRideCaches($rideId);

                Log::info('DriverRideService: Ride updated successfully', [
                    'ride_id' => $rideId
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error updating ride: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'data' => $rideData
            ]);
            return false;
        }
    }

    /**
     * Update ride status with proper state management
     */
    public function updateRideStatus(string $rideId, string $status, array $additionalData = []): bool
    {
        try {
            Log::info('DriverRideService: Updating ride status', [
                'ride_id' => $rideId,
                'status' => $status
            ]);

            // Validate status transition
            $currentRide = $this->getRideById($rideId);
            if (!$currentRide) {
                Log::error('DriverRideService: Ride not found for status update', ['ride_id' => $rideId]);
                return false;
            }

            if (!$this->isValidStatusTransition($currentRide['status'], $status)) {
                Log::error('DriverRideService: Invalid status transition', [
                    'ride_id' => $rideId,
                    'from_status' => $currentRide['status'],
                    'to_status' => $status
                ]);
                return false;
            }

            $updateData = array_merge($additionalData, [
                'status' => $status,
                'status_updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            // Set appropriate timestamps based on status
            $updateData = $this->addStatusTimestamps($updateData, $status);

            return $this->updateRide($rideId, $updateData);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error updating ride status: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'status' => $status
            ]);
            return false;
        }
    }

    /**
     * Complete ride with comprehensive data
     */
    public function completeRide(string $rideId, array $completionData = []): bool
    {
        try {
            Log::info('DriverRideService: Completing ride', [
                'ride_id' => $rideId,
                'completion_data_keys' => array_keys($completionData)
            ]);

            $currentRide = $this->getRideById($rideId);
            if (!$currentRide) {
                Log::error('DriverRideService: Ride not found for completion', ['ride_id' => $rideId]);
                return false;
            }

            // Validate that ride can be completed
            if (!$this->canCompleteRide($currentRide)) {
                Log::error('DriverRideService: Ride cannot be completed', [
                    'ride_id' => $rideId,
                    'current_status' => $currentRide['status']
                ]);
                return false;
            }

            $updateData = array_merge($completionData, [
                'status' => Ride::STATUS_COMPLETED,
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'status_updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            // Auto-calculate fields if not provided
            $updateData = $this->calculateCompletionData($currentRide, $updateData);

            $result = $this->updateRide($rideId, $updateData);

            if ($result) {
                // Trigger completion events
                $this->handleRideCompletion($rideId, $currentRide, $updateData);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error completing ride: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'completion_data' => $completionData
            ]);
            return false;
        }
    }

    /**
     * Cancel ride with reason tracking
     */
    public function cancelRide(string $rideId, string $reason = null, string $cancelledBy = 'system', array $additionalData = []): bool
    {
        try {
            Log::info('DriverRideService: Cancelling ride', [
                'ride_id' => $rideId,
                'reason' => $reason,
                'cancelled_by' => $cancelledBy
            ]);

            $currentRide = $this->getRideById($rideId);
            if (!$currentRide) {
                Log::error('DriverRideService: Ride not found for cancellation', ['ride_id' => $rideId]);
                return false;
            }

            // Validate that ride can be cancelled
            if (!$this->canCancelRide($currentRide)) {
                Log::error('DriverRideService: Ride cannot be cancelled', [
                    'ride_id' => $rideId,
                    'current_status' => $currentRide['status']
                ]);
                return false;
            }

            $updateData = array_merge($additionalData, [
                'status' => Ride::STATUS_CANCELLED,
                'cancelled_at' => now()->format('Y-m-d H:i:s'),
                'cancelled_by' => $cancelledBy,
                'status_updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            if ($reason) {
                $updateData['cancellation_reason'] = $reason;
            }

            $result = $this->updateRide($rideId, $updateData);

            if ($result) {
                // Handle cancellation-specific logic
                $this->handleRideCancellation($rideId, $currentRide, $updateData);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error cancelling ride: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'reason' => $reason
            ]);
            return false;
        }
    }

    /**
     * Get rides by status with caching
     */
    public function getRidesByStatus(string $status, array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Getting rides by status', [
                'status' => $status,
                'filters' => $filters
            ]);

            return $this->rideModel->getRidesByStatus($status, $filters);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting rides by status: ' . $e->getMessage(), [
                'status' => $status,
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Get active rides for driver
     */
    public function getActiveRidesForDriver(string $driverFirebaseUid): array
    {
        try {
            Log::info('DriverRideService: Getting active rides for driver', [
                'driver_uid' => $driverFirebaseUid
            ]);

            return $this->rideModel->getActiveRidesForDriver($driverFirebaseUid);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting active rides for driver: ' . $e->getMessage(), [
                'driver_uid' => $driverFirebaseUid
            ]);
            return [];
        }
    }

    /**
     * Get completed rides for driver with pagination
     */
    public function getCompletedRidesForDriver(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            $filters['status'] = Ride::STATUS_COMPLETED;
            return $this->getDriverRides($driverFirebaseUid, $filters);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting completed rides for driver: ' . $e->getMessage(), [
                'driver_uid' => $driverFirebaseUid
            ]);
            return [];
        }
    }

    /**
     * Get rides in date range
     */
    public function getRidesInDateRange(string $dateFrom, string $dateTo, array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Getting rides in date range', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'filters' => $filters
            ]);

            $filters['date_from'] = $dateFrom;
            $filters['date_to'] = $dateTo;

            return $this->rideModel->getAllRides($filters);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting rides in date range: ' . $e->getMessage(), [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Get comprehensive ride statistics
     */
    public function getRideStatistics(array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Getting ride statistics', ['filters' => $filters]);

            $cacheKey = 'ride_statistics_' . md5(serialize($filters));

            return Cache::remember($cacheKey, 300, function () use ($filters) {
                $stats = $this->rideModel->getRideStatistics($filters);

                // Add additional computed statistics
                $stats['today_rides'] = count($this->getTodayRides());
                $stats['this_week_rides'] = count($this->getThisWeekRides());
                $stats['this_month_rides'] = count($this->getThisMonthRides());
                $stats['average_trip_duration'] = $this->calculateAverageRideDuration($filters);
                $stats['peak_hours'] = $this->calculatePeakHours($filters);

                return $stats;
            });
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting ride statistics: ' . $e->getMessage());
            return $this->getDefaultStatistics();
        }
    }

    /**
     * Get driver-specific ride statistics
     */
    public function getDriverRideStatistics(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Getting driver ride statistics', [
                'driver_uid' => $driverFirebaseUid,
                'filters' => $filters
            ]);

            $cacheKey = "driver_ride_stats_{$driverFirebaseUid}_" . md5(serialize($filters));

            return Cache::remember($cacheKey, 300, function () use ($driverFirebaseUid, $filters) {
                $rides = $this->getDriverRides($driverFirebaseUid, array_merge($filters, ['limit' => 10000]));

                return $this->calculateDriverSpecificStats($rides);
            });
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting driver ride statistics: ' . $e->getMessage(), [
                'driver_uid' => $driverFirebaseUid
            ]);
            return $this->getDefaultStatistics();
        }
    }

    /**
     * Estimate ride fare with dynamic pricing
     */
    public function estimateRideFare(array $rideData): array
    {
        try {
            Log::info('DriverRideService: Estimating ride fare', [
                'distance_km' => $rideData['distance_km'] ?? 0,
                'duration_minutes' => $rideData['duration_minutes'] ?? 0,
                'ride_type' => $rideData['ride_type'] ?? 'standard'
            ]);

            $estimation = $this->calculateFareEstimation($rideData);

            Log::info('DriverRideService: Fare estimation completed', [
                'estimated_fare' => $estimation['total_fare'],
                'breakdown' => $estimation
            ]);

            return $estimation;
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error estimating ride fare: ' . $e->getMessage(), [
                'ride_data' => $rideData
            ]);
            return [
                'base_fare' => 0,
                'distance_fare' => 0,
                'time_fare' => 0,
                'surge_fare' => 0,
                'total_fare' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get ride summary for dashboard
     */
    public function getRideSummaryData(): array
    {
        try {
            Log::info('DriverRideService: Getting ride summary data');

            $cacheKey = 'ride_summary_data';

            return Cache::remember($cacheKey, 60, function () {
                $today = now()->format('Y-m-d H:i:s');
                $weekStart = now()->startOfWeek()->format('Y-m-d H:i:s');
                $monthStart = now()->startOfMonth()->format('Y-m-d H:i:s');

                return [
                    'today' => [
                        'total' => count($this->getTodayRides()),
                        'completed' => count($this->getRidesByStatus(Ride::STATUS_COMPLETED, ['date_from' => $today])),
                        'in_progress' => count($this->getRidesByStatus(Ride::STATUS_IN_PROGRESS, ['date_from' => $today])),
                        'cancelled' => count($this->getRidesByStatus(Ride::STATUS_CANCELLED, ['date_from' => $today]))
                    ],
                    'this_week' => [
                        'total' => count($this->getThisWeekRides()),
                        'completed' => count($this->getRidesByStatus(Ride::STATUS_COMPLETED, ['date_from' => $weekStart])),
                        'in_progress' => count($this->getRidesByStatus(Ride::STATUS_IN_PROGRESS, ['date_from' => $weekStart])),
                        'cancelled' => count($this->getRidesByStatus(Ride::STATUS_CANCELLED, ['date_from' => $weekStart]))
                    ],
                    'this_month' => [
                        'total' => count($this->getThisMonthRides()),
                        'completed' => count($this->getRidesByStatus(Ride::STATUS_COMPLETED, ['date_from' => $monthStart])),
                        'in_progress' => count($this->getRidesByStatus(Ride::STATUS_IN_PROGRESS, ['date_from' => $monthStart])),
                        'cancelled' => count($this->getRidesByStatus(Ride::STATUS_CANCELLED, ['date_from' => $monthStart]))
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error getting ride summary data: ' . $e->getMessage());
            return [];
        }
    }

    // ===================== PRIVATE HELPER METHODS =====================

    /**
     * Validate ride data
     */
    private function validateRideData(array $data): array
    {
        $errors = [];

        $requiredFields = [
            'driver_firebase_uid' => 'Driver is required',
            'pickup_address' => 'Pickup address is required',
            'dropoff_address' => 'Dropoff address is required'
        ];

        foreach ($requiredFields as $field => $message) {
            if (empty($data[$field])) {
                $errors[] = $message;
            }
        }

        // Validate coordinates if provided
        if (
            isset($data['pickup_latitude']) &&
            ($data['pickup_latitude'] < -90 || $data['pickup_latitude'] > 90)
        ) {
            $errors[] = 'Invalid pickup latitude';
        }

        // Validate fares if provided
        if (isset($data['estimated_fare']) && $data['estimated_fare'] < 0) {
            $errors[] = 'Estimated fare cannot be negative';
        }

        return ['errors' => $errors, 'valid' => empty($errors)];
    }

    /**
     * Validate ride update
     */
    private function validateRideUpdate(array $updateData, array $currentRide): array
    {
        $errors = [];

        // Check if ride is in a state that allows updates
        if (in_array($currentRide['status'], [Ride::STATUS_COMPLETED, Ride::STATUS_CANCELLED])) {
            if (
                isset($updateData['status']) &&
                $updateData['status'] !== $currentRide['status']
            ) {
                $errors[] = 'Cannot change status of completed or cancelled ride';
            }
        }

        return ['errors' => $errors, 'valid' => empty($errors)];
    }

    /**
     * Check if status transition is valid
     */
    private function isValidStatusTransition(string $fromStatus, string $toStatus): bool
    {
        $validTransitions = [
            Ride::STATUS_PENDING => [Ride::STATUS_REQUESTED, Ride::STATUS_CANCELLED],
            Ride::STATUS_REQUESTED => [Ride::STATUS_ACCEPTED, Ride::STATUS_CANCELLED],
            Ride::STATUS_ACCEPTED => [Ride::STATUS_DRIVER_ARRIVED, Ride::STATUS_IN_PROGRESS, Ride::STATUS_CANCELLED],
            Ride::STATUS_DRIVER_ARRIVED => [Ride::STATUS_IN_PROGRESS, Ride::STATUS_CANCELLED],
            Ride::STATUS_IN_PROGRESS => [Ride::STATUS_COMPLETED, Ride::STATUS_CANCELLED],
            Ride::STATUS_COMPLETED => [], // No transitions from completed
            Ride::STATUS_CANCELLED => []  // No transitions from cancelled
        ];

        return in_array($toStatus, $validTransitions[$fromStatus] ?? []);
    }

    /**
     * Add status-specific timestamps
     */
    private function addStatusTimestamps(array $updateData, string $status): array
    {
        switch ($status) {
            case Ride::STATUS_REQUESTED:
                $updateData['requested_at'] = now()->format('Y-m-d H:i:s');
                break;
            case Ride::STATUS_ACCEPTED:
                $updateData['accepted_at'] = now()->format('Y-m-d H:i:s');
                break;
            case Ride::STATUS_DRIVER_ARRIVED:
                $updateData['driver_arrived_at'] = now()->format('Y-m-d H:i:s');
                break;
            case Ride::STATUS_IN_PROGRESS:
                $updateData['started_at'] = now()->format('Y-m-d H:i:s');
                break;
            case Ride::STATUS_COMPLETED:
                $updateData['completed_at'] = now()->format('Y-m-d H:i:s');
                break;
            case Ride::STATUS_CANCELLED:
                $updateData['cancelled_at'] = now()->format('Y-m-d H:i:s');
                break;
        }

        return $updateData;
    }

    /**
     * Check if ride can be completed
     */
    private function canCompleteRide(array $ride): bool
    {
        $completableStatuses = [
            Ride::STATUS_IN_PROGRESS,
            Ride::STATUS_DRIVER_ARRIVED
        ];

        return in_array($ride['status'], $completableStatuses);
    }

    /**
     * Check if ride can be cancelled
     */
    private function canCancelRide(array $ride): bool
    {
        $cancellableStatuses = [
            Ride::STATUS_PENDING,
            Ride::STATUS_REQUESTED,
            Ride::STATUS_ACCEPTED,
            Ride::STATUS_DRIVER_ARRIVED,
            Ride::STATUS_IN_PROGRESS
        ];

        return in_array($ride['status'], $cancellableStatuses);
    }

    /**
     * Calculate completion data automatically
     */
    private function calculateCompletionData(array $currentRide, array $updateData): array
    {
        // If actual fare is not provided, use estimated fare
        if (!isset($updateData['actual_fare']) && isset($currentRide['estimated_fare'])) {
            $updateData['actual_fare'] = $currentRide['estimated_fare'];
        }

        // Calculate driver earnings if not provided (80% of fare)
        if (!isset($updateData['driver_earnings']) && isset($updateData['actual_fare'])) {
            $updateData['driver_earnings'] = $updateData['actual_fare'] * 0.8;
            $updateData['commission'] = $updateData['actual_fare'] * 0.2;
        }

        // Calculate duration if timestamps are available
        if (
            !isset($updateData['duration_minutes']) &&
            isset($currentRide['started_at']) &&
            isset($updateData['completed_at'])
        ) {
            $startTime = Carbon::parse($currentRide['started_at']);
            $endTime = Carbon::parse($updateData['completed_at']);
            $updateData['duration_minutes'] = $endTime->diffInMinutes($startTime);
        }

        return $updateData;
    }

    /**
     * Enrich ride data before creation
     */
    private function enrichRideData(array $rideData): array
    {
        // Add ride ID if not provided
        if (!isset($rideData['ride_id'])) {
            $rideData['ride_id'] = 'RIDE_' . strtoupper(uniqid());
        }

        // Add timestamps
        $rideData['requested_at'] = now()->format('Y-m-d H:i:s');

        // Estimate fare if not provided
        if (
            !isset($rideData['estimated_fare']) &&
            isset($rideData['distance_km'], $rideData['duration_minutes'])
        ) {
            $fareEstimation = $this->estimateRideFare($rideData);
            $rideData['estimated_fare'] = $fareEstimation['total_fare'];
        }

        return $rideData;
    }

    /**
     * Calculate fare estimation with dynamic pricing
     */
    private function calculateFareEstimation(array $rideData): array
    {
        $baseFare = 5.00;
        $perKmRate = 1.50;
        $perMinuteRate = 0.25;

        // Ride type multipliers
        $typeMultipliers = [
            'standard' => 1.0,
            'premium' => 1.5,
            'xl' => 1.3,
            'shared' => 0.8,
            'delivery' => 0.9
        ];

        $distanceKm = (float) ($rideData['distance_km'] ?? 0);
        $durationMinutes = (int) ($rideData['duration_minutes'] ?? 0);
        $rideType = $rideData['ride_type'] ?? 'standard';
        $surgeMultiplier = (float) ($rideData['surge_multiplier'] ?? 1.0);

        $typeMultiplier = $typeMultipliers[$rideType] ?? 1.0;

        $distanceFare = $distanceKm * $perKmRate * $typeMultiplier;
        $timeFare = $durationMinutes * $perMinuteRate * $typeMultiplier;
        $subtotal = $baseFare + $distanceFare + $timeFare;
        $surgeFare = ($surgeMultiplier > 1) ? $subtotal * ($surgeMultiplier - 1) : 0;
        $totalFare = $subtotal + $surgeFare;

        return [
            'base_fare' => round($baseFare, 2),
            'distance_fare' => round($distanceFare, 2),
            'time_fare' => round($timeFare, 2),
            'type_multiplier' => $typeMultiplier,
            'surge_multiplier' => $surgeMultiplier,
            'surge_fare' => round($surgeFare, 2),
            'subtotal' => round($subtotal, 2),
            'total_fare' => round($totalFare, 2)
        ];
    }

    /**
     * Calculate driver-specific statistics
     */
    private function calculateDriverSpecificStats(array $rides): array
    {
        $stats = [
            'total_rides' => count($rides),
            'completed_rides' => 0,
            'cancelled_rides' => 0,
            'in_progress_rides' => 0,
            'total_earnings' => 0,
            'average_rating' => 0,
            'total_distance' => 0,
            'total_duration' => 0,
            'completion_rate' => 0,
            'cancellation_rate' => 0,
            'average_earnings_per_ride' => 0
        ];

        if (empty($rides)) {
            return $stats;
        }

        $totalRating = 0;
        $ratedRides = 0;

        foreach ($rides as $ride) {
            // Count by status
            switch ($ride['status']) {
                case Ride::STATUS_COMPLETED:
                    $stats['completed_rides']++;
                    break;
                case Ride::STATUS_CANCELLED:
                    $stats['cancelled_rides']++;
                    break;
                case Ride::STATUS_IN_PROGRESS:
                case Ride::STATUS_ACCEPTED:
                case Ride::STATUS_DRIVER_ARRIVED:
                    $stats['in_progress_rides']++;
                    break;
            }

            // Sum earnings and other metrics
            if (isset($ride['driver_earnings'])) {
                $stats['total_earnings'] += (float) $ride['driver_earnings'];
            } elseif (isset($ride['actual_fare'])) {
                $stats['total_earnings'] += (float) $ride['actual_fare'] * 0.8; // 80% to driver
            }

            if (isset($ride['distance_km'])) {
                $stats['total_distance'] += (float) $ride['distance_km'];
            }

            if (isset($ride['duration_minutes'])) {
                $stats['total_duration'] += (int) $ride['duration_minutes'];
            }

            if (isset($ride['driver_rating']) && $ride['driver_rating'] > 0) {
                $totalRating += (float) $ride['driver_rating'];
                $ratedRides++;
            }
        }

        // Calculate rates and averages
        if ($stats['total_rides'] > 0) {
            $stats['completion_rate'] = round(($stats['completed_rides'] / $stats['total_rides']) * 100, 2);
            $stats['cancellation_rate'] = round(($stats['cancelled_rides'] / $stats['total_rides']) * 100, 2);
        }

        if ($ratedRides > 0) {
            $stats['average_rating'] = round($totalRating / $ratedRides, 2);
        }

        if ($stats['completed_rides'] > 0) {
            $stats['average_earnings_per_ride'] = round($stats['total_earnings'] / $stats['completed_rides'], 2);
        }

        return $stats;
    }

    /**
     * Get today's rides
     */
    private function getTodayRides(): array
    {
        return $this->getRidesInDateRange(
            now()->startOfDay()->format('Y-m-d H:i:s'),
            now()->endOfDay()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Get this week's rides
     */
    private function getThisWeekRides(): array
    {
        return $this->getRidesInDateRange(
            now()->startOfWeek()->format('Y-m-d H:i:s'),
            now()->endOfWeek()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Get this month's rides
     */
    private function getThisMonthRides(): array
    {
        return $this->getRidesInDateRange(
            now()->startOfMonth()->format('Y-m-d H:i:s'),
            now()->endOfMonth()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Calculate average ride duration
     */
    private function calculateAverageRideDuration(array $filters): float
    {
        $rides = $this->getAllRides(array_merge($filters, ['limit' => 1000]));
        $completedRides = array_filter($rides, function ($ride) {
            return $ride['status'] === Ride::STATUS_COMPLETED &&
                isset($ride['duration_minutes']) &&
                $ride['duration_minutes'] > 0;
        });

        if (empty($completedRides)) {
            return 0;
        }

        $totalDuration = array_sum(array_column($completedRides, 'duration_minutes'));
        return round($totalDuration / count($completedRides), 2);
    }

    /**
     * Calculate peak hours
     */
    private function calculatePeakHours(array $filters): array
    {
        $rides = $this->getAllRides(array_merge($filters, ['limit' => 1000]));
        $hourCounts = [];

        foreach ($rides as $ride) {
            if (isset($ride['created_at'])) {
                $hour = Carbon::parse($ride['created_at'])->format('H');
                $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
            }
        }

        arsort($hourCounts);
        return array_slice($hourCounts, 0, 3, true);
    }

    /**
     * Handle ride completion events
     */
    private function handleRideCompletion(string $rideId, array $currentRide, array $completionData): void
    {
        try {
            Log::info('DriverRideService: Ride completion handled', [
                'ride_id' => $rideId,
                'driver_uid' => $currentRide['driver_firebase_uid']
            ]);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error handling ride completion: ' . $e->getMessage());
        }
    }

    /**
     * Handle ride cancellation events
     */
    private function handleRideCancellation(string $rideId, array $currentRide, array $cancellationData): void
    {
        try {
            Log::info('DriverRideService: Ride cancellation handled', [
                'ride_id' => $rideId,
                'cancelled_by' => $cancellationData['cancelled_by']
            ]);
        } catch (\Exception $e) {
            Log::error('DriverRideService: Error handling ride cancellation: ' . $e->getMessage());
        }
    }

    /**
     * Clear ride-specific caches
     */
    private function clearRideCaches(string $rideId): void
    {
        Cache::forget("ride_{$rideId}");
        $this->clearRidesCaches();
    }

    /**
     * Clear general rides caches
     */
    private function clearRidesCaches(): void
    {
        Cache::flush(); // Simplified cache clearing
    }

    /**
     * Get default statistics structure
     */
    private function getDefaultStatistics(): array
    {
        return [
            'total_rides' => 0,
            'completed_rides' => 0,
            'cancelled_rides' => 0,
            'in_progress_rides' => 0,
            'pending_rides' => 0,
            'total_earnings' => 0,
            'average_rating' => 0,
            'total_distance' => 0,
            'total_duration' => 0,
            'completion_rate' => 0,
            'cancellation_rate' => 0,
            'average_earnings_per_ride' => 0,
            'today_rides' => 0,
            'this_week_rides' => 0,
            'this_month_rides' => 0
        ];
    }
}
