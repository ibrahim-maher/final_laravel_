<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DriverStatisticsService
{
    protected $driverProfileService;
    protected $driverRideService;
    protected $driverActivityService;
    protected $driverDocumentService;
    protected $driverVehicleService;

    public function __construct(
        DriverProfileService $driverProfileService,
        DriverRideService $driverRideService,
        DriverActivityService $driverActivityService,
        DriverDocumentService $driverDocumentService,
        DriverVehicleService $driverVehicleService
    ) {
        $this->driverProfileService = $driverProfileService;
        $this->driverRideService = $driverRideService;
        $this->driverActivityService = $driverActivityService;
        $this->driverDocumentService = $driverDocumentService;
        $this->driverVehicleService = $driverVehicleService;
    }

    /**
     * Get comprehensive driver statistics
     */
    public function getDriverStatistics(): array
    {
        try {
            $drivers = $this->driverProfileService->getAllDrivers(['limit' => 1000]);
            
            $stats = $this->initializeDriverStats();
            
            $oneWeekAgo = now()->subWeek();
            $totalRating = 0;
            $ratedDrivers = 0;

            foreach ($drivers as $driver) {
                $this->processDriverForStats($driver, $stats, $oneWeekAgo, $totalRating, $ratedDrivers);
            }

            // Calculate average rating
            if ($ratedDrivers > 0) {
                $stats['average_rating'] = round($totalRating / $ratedDrivers, 2);
            }

            Log::debug('Driver statistics calculated', $stats);
            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting driver statistics: ' . $e->getMessage());
            return $this->initializeDriverStats();
        }
    }

    /**
     * Get driver performance metrics
     */
    public function getDriverPerformanceMetrics(string $driverFirebaseUid): array
    {
        try {
            $driver = $this->driverProfileService->getDriverById($driverFirebaseUid);
            $rideStats = $this->driverRideService->getDriverRideStatistics($driverFirebaseUid);
            $activityStats = $this->driverActivityService->getDriverActivityStatistics($driverFirebaseUid);
            $documents = $this->driverDocumentService->getDriverDocuments($driverFirebaseUid);
            $vehicles = $this->driverVehicleService->getDriverVehicles($driverFirebaseUid);

            return [
                'driver_info' => $driver,
                'ride_statistics' => $rideStats,
                'activity_statistics' => $activityStats,
                'documents_count' => count($documents),
                'vehicles_count' => count($vehicles),
                'verification_status' => $driver['verification_status'] ?? 'pending',
                'account_age_days' => $this->calculateAccountAge($driver['join_date'] ?? now()),
                'last_active' => $driver['last_active'] ?? null,
                'compliance_score' => $this->calculateComplianceScore($driverFirebaseUid),
                'performance_score' => $this->calculatePerformanceScore($rideStats)
            ];
        } catch (\Exception $e) {
            Log::error('Error getting driver performance metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system-wide analytics
     */
    public function getSystemAnalytics(): array
    {
        try {
            $driverStats = $this->getDriverStatistics();
            $activityStats = $this->driverActivityService->getSystemActivityStatistics();
            
            return [
                'driver_statistics' => $driverStats,
                'activity_statistics' => $activityStats,
                'document_statistics' => $this->getDocumentStatistics(),
                'vehicle_statistics' => $this->getVehicleStatistics(),
                'performance_metrics' => $this->getSystemPerformanceMetrics(),
                'growth_metrics' => $this->getGrowthMetrics()
            ];
        } catch (\Exception $e) {
            Log::error('Error getting system analytics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get document statistics
     */
    public function getDocumentStatistics(): array
    {
        try {
            $pendingDocs = $this->driverDocumentService->getPendingDocuments();
            $expiredDocs = $this->driverDocumentService->getExpiredDocuments();
            $expiringSoonDocs = $this->driverDocumentService->getDocumentsExpiringSoon();

            return [
                'pending_verification' => count($pendingDocs),
                'expired_documents' => count($expiredDocs),
                'expiring_soon' => count($expiringSoonDocs),
                'total_documents' => $this->driverDocumentService->countDocuments(),
                'verification_queue_size' => count($pendingDocs)
            ];
        } catch (\Exception $e) {
            Log::error('Error getting document statistics: ' . $e->getMessage());
            return [
                'pending_verification' => 0,
                'expired_documents' => 0,
                'expiring_soon' => 0,
                'total_documents' => 0,
                'verification_queue_size' => 0
            ];
        }
    }

    /**
     * Get vehicle statistics
     */
    public function getVehicleStatistics(): array
    {
        try {
            $activeVehicles = $this->driverVehicleService->getVehiclesByStatus('active');
            $pendingVerification = $this->driverVehicleService->getVehiclesByVerificationStatus('pending');

            return [
                'total_vehicles' => $this->driverVehicleService->countDocuments(),
                'active_vehicles' => count($activeVehicles),
                'pending_verification' => count($pendingVerification),
                'vehicles_per_driver' => $this->calculateVehiclesPerDriver()
            ];
        } catch (\Exception $e) {
            Log::error('Error getting vehicle statistics: ' . $e->getMessage());
            return [
                'total_vehicles' => 0,
                'active_vehicles' => 0,
                'pending_verification' => 0,
                'vehicles_per_driver' => 0
            ];
        }
    }

    /**
     * Get growth metrics
     */
    public function getGrowthMetrics(): array
    {
        try {
            $drivers = $this->driverProfileService->getAllDrivers(['limit' => 10000]);
            
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            $weekAgo = now()->subWeek()->startOfDay();
            $monthAgo = now()->subMonth()->startOfDay();

            $metrics = [
                'new_drivers_today' => 0,
                'new_drivers_yesterday' => 0,
                'new_drivers_this_week' => 0,
                'new_drivers_this_month' => 0,
                'growth_rate_daily' => 0,
                'growth_rate_weekly' => 0,
                'growth_rate_monthly' => 0
            ];

            foreach ($drivers as $driver) {
                $joinDate = Carbon::parse($driver['join_date'] ?? now());

                if ($joinDate->gte($today)) {
                    $metrics['new_drivers_today']++;
                }

                if ($joinDate->gte($yesterday) && $joinDate->lt($today)) {
                    $metrics['new_drivers_yesterday']++;
                }

                if ($joinDate->gte($weekAgo)) {
                    $metrics['new_drivers_this_week']++;
                }

                if ($joinDate->gte($monthAgo)) {
                    $metrics['new_drivers_this_month']++;
                }
            }

            // Calculate growth rates
            $totalDrivers = count($drivers);
            if ($totalDrivers > 0) {
                $metrics['growth_rate_daily'] = round(($metrics['new_drivers_today'] / $totalDrivers) * 100, 2);
                $metrics['growth_rate_weekly'] = round(($metrics['new_drivers_this_week'] / $totalDrivers) * 100, 2);
                $metrics['growth_rate_monthly'] = round(($metrics['new_drivers_this_month'] / $totalDrivers) * 100, 2);
            }

            return $metrics;
        } catch (\Exception $e) {
            Log::error('Error getting growth metrics: ' . $e->getMessage());
            return [
                'new_drivers_today' => 0,
                'new_drivers_yesterday' => 0,
                'new_drivers_this_week' => 0,
                'new_drivers_this_month' => 0,
                'growth_rate_daily' => 0,
                'growth_rate_weekly' => 0,
                'growth_rate_monthly' => 0
            ];
        }
    }

    /**
     * Get system performance metrics
     */
    public function getSystemPerformanceMetrics(): array
    {
        try {
            $drivers = $this->driverProfileService->getAllDrivers(['limit' => 10000]);
            
            $totalEarnings = 0;
            $totalRides = 0;
            $totalRating = 0;
            $ratedDrivers = 0;
            $activeDrivers = 0;

            foreach ($drivers as $driver) {
                if (isset($driver['total_earnings'])) {
                    $totalEarnings += (float) $driver['total_earnings'];
                }

                if (isset($driver['total_rides'])) {
                    $totalRides += (int) $driver['total_rides'];
                }

                if (isset($driver['rating']) && $driver['rating'] > 0) {
                    $totalRating += (float) $driver['rating'];
                    $ratedDrivers++;
                }

                if (($driver['status'] ?? '') === Driver::STATUS_ACTIVE) {
                    $activeDrivers++;
                }
            }

            return [
                'total_earnings' => $totalEarnings,
                'total_rides' => $totalRides,
                'average_system_rating' => $ratedDrivers > 0 ? round($totalRating / $ratedDrivers, 2) : 0,
                'active_driver_percentage' => count($drivers) > 0 ? round(($activeDrivers / count($drivers)) * 100, 2) : 0,
                'average_rides_per_driver' => count($drivers) > 0 ? round($totalRides / count($drivers), 2) : 0,
                'average_earnings_per_driver' => count($drivers) > 0 ? round($totalEarnings / count($drivers), 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Error getting system performance metrics: ' . $e->getMessage());
            return [
                'total_earnings' => 0,
                'total_rides' => 0,
                'average_system_rating' => 0,
                'active_driver_percentage' => 0,
                'average_rides_per_driver' => 0,
                'average_earnings_per_driver' => 0
            ];
        }
    }

    /**
     * Calculate compliance score for driver
     */
    private function calculateComplianceScore(string $driverFirebaseUid): float
    {
        try {
            $documents = $this->driverDocumentService->getDriverDocuments($driverFirebaseUid);
            $vehicles = $this->driverVehicleService->getDriverVehicles($driverFirebaseUid);
            $driver = $this->driverProfileService->getDriverById($driverFirebaseUid);

            $score = 0;
            $maxScore = 100;

            // Profile completion (30 points)
            $profileFields = ['name', 'email', 'phone', 'date_of_birth', 'address', 'license_number'];
            $completedFields = 0;
            foreach ($profileFields as $field) {
                if (!empty($driver[$field])) {
                    $completedFields++;
                }
            }
            $score += ($completedFields / count($profileFields)) * 30;

            // Document verification (40 points)
            $verifiedDocs = array_filter($documents, function($doc) {
                return ($doc['verification_status'] ?? '') === 'verified';
            });
            $requiredDocTypes = $this->driverDocumentService->getMissingRequiredDocuments($driverFirebaseUid);
            $hasAllRequired = empty($requiredDocTypes);
            
            if ($hasAllRequired) {
                $score += 40;
            } else {
                $score += (count($verifiedDocs) / max(1, count($documents))) * 40;
            }

            // Vehicle verification (20 points)
            $verifiedVehicles = array_filter($vehicles, function($vehicle) {
                return ($vehicle['verification_status'] ?? '') === 'verified';
            });
            if (count($vehicles) > 0) {
                $score += (count($verifiedVehicles) / count($vehicles)) * 20;
            }

            // Verification status (10 points)
            if (($driver['verification_status'] ?? '') === 'verified') {
                $score += 10;
            }

            return round(min($score, $maxScore), 2);
        } catch (\Exception $e) {
            Log::error('Error calculating compliance score: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calculate performance score for driver
     */
    private function calculatePerformanceScore(array $rideStats): float
    {
        try {
            $score = 0;
            $maxScore = 100;

            // Rating (40 points)
            $rating = $rideStats['average_rating'] ?? 0;
            $score += ($rating / 5) * 40;

            // Completion rate (30 points)
            $completionRate = $rideStats['completion_rate'] ?? 0;
            $score += ($completionRate / 100) * 30;

            // Total rides (20 points) - logarithmic scale
            $totalRides = $rideStats['total_rides'] ?? 0;
            if ($totalRides > 0) {
                $ridesScore = min(log($totalRides + 1) / log(101), 1) * 20; // Scale to max 20 points at 100 rides
                $score += $ridesScore;
            }

            // Low cancellation rate (10 points)
            $cancellationRate = $rideStats['cancellation_rate'] ?? 0;
            $score += max(0, (100 - $cancellationRate) / 100) * 10;

            return round(min($score, $maxScore), 2);
        } catch (\Exception $e) {
            Log::error('Error calculating performance score: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calculate account age in days
     */
    private function calculateAccountAge(string $joinDate): int
    {
        try {
            $join = Carbon::parse($joinDate);
            return $join->diffInDays(now());
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate vehicles per driver
     */
    private function calculateVehiclesPerDriver(): float
    {
        try {
            $totalVehicles = $this->driverVehicleService->countDocuments();
            $totalDrivers = $this->driverProfileService->getTotalDriversCount();

            return $totalDrivers > 0 ? round($totalVehicles / $totalDrivers, 2) : 0;
        } catch (\Exception $e) {
            Log::error('Error calculating vehicles per driver: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Initialize driver statistics array
     */
    private function initializeDriverStats(): array
    {
        return [
            'total_drivers' => 0,
            'active_drivers' => 0,
            'inactive_drivers' => 0,
            'suspended_drivers' => 0,
            'verified_drivers' => 0,
            'pending_verification' => 0,
            'available_drivers' => 0,
            'busy_drivers' => 0,
            'offline_drivers' => 0,
            'recent_registrations' => 0,
            'drivers_by_status' => [],
            'drivers_by_verification' => [],
            'drivers_by_availability' => [],
            'average_rating' => 0,
            'total_rides' => 0,
            'total_earnings' => 0
        ];
    }

    /**
     * Process individual driver for statistics
     */
    private function processDriverForStats(array $driver, array &$stats, Carbon $oneWeekAgo, float &$totalRating, int &$ratedDrivers): void
    {
        $stats['total_drivers']++;

        // Count by status
        $status = $driver['status'] ?? Driver::STATUS_PENDING;
        $stats['drivers_by_status'][$status] = ($stats['drivers_by_status'][$status] ?? 0) + 1;
        
        switch ($status) {
            case Driver::STATUS_ACTIVE:
                $stats['active_drivers']++;
                break;
            case Driver::STATUS_INACTIVE:
                $stats['inactive_drivers']++;
                break;
            case Driver::STATUS_SUSPENDED:
                $stats['suspended_drivers']++;
                break;
        }

        // Count by verification status
        $verification = $driver['verification_status'] ?? Driver::VERIFICATION_PENDING;
        $stats['drivers_by_verification'][$verification] = ($stats['drivers_by_verification'][$verification] ?? 0) + 1;
        
        if ($verification === Driver::VERIFICATION_VERIFIED) {
            $stats['verified_drivers']++;
        } else {
            $stats['pending_verification']++;
        }

        // Count by availability
        $availability = $driver['availability_status'] ?? Driver::AVAILABILITY_OFFLINE;
        $stats['drivers_by_availability'][$availability] = ($stats['drivers_by_availability'][$availability] ?? 0) + 1;
        
        switch ($availability) {
            case Driver::AVAILABILITY_AVAILABLE:
                $stats['available_drivers']++;
                break;
            case Driver::AVAILABILITY_BUSY:
                $stats['busy_drivers']++;
                break;
            case Driver::AVAILABILITY_OFFLINE:
                $stats['offline_drivers']++;
                break;
        }

        // Recent registrations
        if (isset($driver['join_date'])) {
            try {
                $joinDate = Carbon::parse($driver['join_date']);
                if ($joinDate->gte($oneWeekAgo)) {
                    $stats['recent_registrations']++;
                }
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        // Aggregated metrics
        if (isset($driver['rating']) && $driver['rating'] > 0) {
            $totalRating += (float) $driver['rating'];
            $ratedDrivers++;
        }

        if (isset($driver['total_rides'])) {
            $stats['total_rides'] += (int) $driver['total_rides'];
        }

        if (isset($driver['total_earnings'])) {
            $stats['total_earnings'] += (float) $driver['total_earnings'];
        }
    }
}