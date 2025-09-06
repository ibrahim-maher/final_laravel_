<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Ride;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DriverRideService extends BaseService
{
    protected $collection = 'rides';

    /**
     * Get rides in date range
     */
    public function getRidesInDateRange(string $dateFrom, string $dateTo, array $filters = []): array
    {
        return $this->getAllDocuments(array_merge($filters, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]));
    }

    /**
     * Complete ride
     */
    public function completeRide(string $rideId, array $completionData = []): bool
    {
        try {
            $updateData = array_merge($completionData, [
                'status' => Ride::STATUS_COMPLETED,
                'completed_at' => now()->toDateTimeString(),
                'status_updated_at' => now()->toDateTimeString()
            ]);
            
            return $this->updateDocument($rideId, $updateData);
        } catch (\Exception $e) {
            Log::error('Error completing ride: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel ride
     */
    public function cancelRide(string $rideId, string $reason = null): bool
    {
        try {
            $updateData = [
                'status' => Ride::STATUS_CANCELLED,
                'cancelled_at' => now()->toDateTimeString(),
                'status_updated_at' => now()->toDateTimeString()
            ];
            
            if ($reason) {
                $updateData['cancellation_reason'] = $reason;
            }
            
            return $this->updateDocument($rideId, $updateData);
        } catch (\Exception $e) {
            Log::error('Error cancelling ride: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Initialize statistics array
     */
    private function initializeStats(): array
    {
        return [
            'total_rides' => 0,
            'completed_rides' => 0,
            'cancelled_rides' => 0,
            'in_progress_rides' => 0,
            'total_earnings' => 0,
            'average_rating' => 0,
            'total_distance' => 0,
            'total_duration' => 0,
            'completion_rate' => 0,
            'cancellation_rate' => 0,
            'today_rides' => 0,
            'this_week_rides' => 0,
            'this_month_rides' => 0
        ];
    }

    /**
     * Process individual ride for statistics
     */
    private function processRideForStats(array $ride, array &$stats, float &$totalRating, int &$ratedRides, Carbon $today, Carbon $weekStart, Carbon $monthStart): void
    {
        $rideDate = Carbon::parse($ride['created_at'] ?? now());
        
        // Count by status
        switch ($ride['status'] ?? '') {
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
        
        // Earnings
        if (isset($ride['driver_earnings'])) {
            $stats['total_earnings'] += (float) $ride['driver_earnings'];
        }
        
        // Rating
        if (isset($ride['driver_rating']) && $ride['driver_rating'] > 0) {
            $totalRating += (float) $ride['driver_rating'];
            $ratedRides++;
        }
        
        // Distance and duration
        if (isset($ride['distance_km'])) {
            $stats['total_distance'] += (float) $ride['distance_km'];
        }
        
        if (isset($ride['duration_minutes'])) {
            $stats['total_duration'] += (int) $ride['duration_minutes'];
        }
        
        // Date-based counts
        if ($rideDate->gte($today)) {
            $stats['today_rides']++;
        }
        
        if ($rideDate->gte($weekStart)) {
            $stats['this_week_rides']++;
        }
        
        if ($rideDate->gte($monthStart)) {
            $stats['this_month_rides']++;
        }
    }

    /**
     * Calculate completion rates and averages
     */
    private function calculateStatsRates(array &$stats, float $totalRating, int $ratedRides): void
    {
        $stats['total_rides'] = $stats['completed_rides'] + $stats['cancelled_rides'] + $stats['in_progress_rides'];
        
        // Calculate rates
        if ($stats['total_rides'] > 0) {
            $stats['completion_rate'] = round(($stats['completed_rides'] / $stats['total_rides']) * 100, 2);
            $stats['cancellation_rate'] = round(($stats['cancelled_rides'] / $stats['total_rides']) * 100, 2);
        }
        
        // Calculate average rating
        if ($ratedRides > 0) {
            $stats['average_rating'] = round($totalRating / $ratedRides, 2);
        }
    }

    /**
     * Set ride defaults
     */
    private function setRideDefaults(array $rideData): array
    {
        return array_merge([
            'status' => Ride::STATUS_PENDING,
            'driver_rating' => 0,
            'passenger_rating' => 0,
            'driver_earnings' => 0.00,
            'distance_km' => 0,
            'duration_minutes' => 0
        ], $rideData);
    }

    /**
     * Check if document matches search query
     */
    protected function matchesSearch(array $document, string $search): bool
    {
        $searchableFields = ['passenger_name', 'pickup_address', 'destination_address', 'status'];
        
        foreach ($searchableFields as $field) {
            if (isset($document[$field]) && stripos($document[$field], $search) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get driver's rides
     */
    public function getDriverRides(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('Getting rides for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            // Get all rides and filter by driver
            $allRides = $this->getAllDocuments(['limit' => $filters['limit'] ?? 1000]);
            
            $driverRides = $this->filterByField($allRides, 'driver_firebase_uid', $driverFirebaseUid);
            
            // Apply additional filters
            return $this->applyFilters($driverRides, $filters);
        } catch (\Exception $e) {
            Log::error('Error getting driver rides: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get driver's ride statistics
     */
    public function getDriverRideStatistics(string $driverFirebaseUid): array
    {
        try {
            $rides = $this->getDriverRides($driverFirebaseUid, ['limit' => 10000]);
            
            $stats = $this->initializeStats();
            
            $totalRating = 0;
            $ratedRides = 0;
            $today = now()->startOfDay();
            $weekStart = now()->startOfWeek();
            $monthStart = now()->startOfMonth();
            
            foreach ($rides as $ride) {
                $this->processRideForStats($ride, $stats, $totalRating, $ratedRides, $today, $weekStart, $monthStart);
            }
            
            // Calculate rates and averages
            $this->calculateStatsRates($stats, $totalRating, $ratedRides);
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting driver ride statistics: ' . $e->getMessage());
            return $this->initializeStats();
        }
    }

    /**
     * Create new ride
     */
    public function createRide(array $rideData): ?array
    {
        try {
            Log::info('Creating new ride', ['driver_uid' => $rideData['driver_firebase_uid'] ?? 'unknown']);
            
            $rideData = $this->setRideDefaults($rideData);
            
            return $this->createDocument($rideData);
        } catch (\Exception $e) {
            Log::error('Error creating ride: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update ride
     */
    public function updateRide(string $rideId, array $rideData): bool
    {
        try {
            Log::info('Updating ride', ['ride_id' => $rideId]);
            
            return $this->updateDocument($rideId, $rideData);
        } catch (\Exception $e) {
            Log::error('Error updating ride: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update ride status
     */
    public function updateRideStatus(string $rideId, string $status): bool
    {
        try {
            Log::info('Updating ride status', ['ride_id' => $rideId, 'status' => $status]);
            
            $updateData = [
                'status' => $status,
                'status_updated_at' => now()->toDateTimeString()
            ];
            
            // Set completion time for completed rides
            if ($status === Ride::STATUS_COMPLETED) {
                $updateData['completed_at'] = now()->toDateTimeString();
            }
            
            return $this->updateDocument($rideId, $updateData);
        } catch (\Exception $e) {
            Log::error('Error updating ride status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get ride by ID
     */
    public function getRideById(string $rideId): ?array
    {
        return $this->getDocumentById($rideId);
    }

    /**
     * Get rides by status
     */
    public function getRidesByStatus(string $status, array $filters = []): array
    {
        return $this->getAllDocuments(array_merge($filters, ['status' => $status]));
    }

    /**
     * Get active rides for driver
     */
    public function getActiveRidesForDriver(string $driverFirebaseUid): array
    {
        $activeStatuses = [
            Ride::STATUS_PENDING,
            Ride::STATUS_ACCEPTED,
            Ride::STATUS_DRIVER_ARRIVED,
            Ride::STATUS_IN_PROGRESS
        ];
        
        $rides = $this->getDriverRides($driverFirebaseUid, ['limit' => 100]);
        
        return array_filter($rides, function($ride) use ($activeStatuses) {
            return in_array($ride['status'] ?? '', $activeStatuses);
        });
    }

    /**
     * Get completed rides for driver
     */
    public function getCompletedRidesForDriver(string $driverFirebaseUid): array
    {
        return $this->getDriverRides($driverFirebaseUid, [
            'status' => Ride::STATUS_COMPLETED,
            'limit' => 100
        ]);
    }
}