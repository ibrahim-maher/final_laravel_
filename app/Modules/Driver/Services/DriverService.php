<?php

namespace App\Modules\Driver\Services;

use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;

/**
 * Main Driver Service - Orchestrates all driver-related operations
 * This service acts as a facade/coordinator for all specialized driver services
 */
class DriverService
{
    protected $profileService;
    protected $rideService;
    protected $vehicleService;
    protected $documentService;
    protected $activityService;
    protected $statisticsService;
    protected $bulkService;

    public function __construct(
        DriverProfileService $profileService,
        DriverRideService $rideService,
        DriverVehicleService $vehicleService,
        DriverDocumentService $documentService,
        DriverActivityService $activityService,
        DriverStatisticsService $statisticsService,
        DriverBulkService $bulkService
    ) {
        $this->profileService = $profileService;
        $this->rideService = $rideService;
        $this->vehicleService = $vehicleService;
        $this->documentService = $documentService;
        $this->activityService = $activityService;
        $this->statisticsService = $statisticsService;
        $this->bulkService = $bulkService;
    }

    // ============ DRIVER PROFILE OPERATIONS ============

    /**
     * Get all drivers with optional filters
     */
    public function getAllDrivers(array $filters = []): array
    {
        
        return $this->profileService->getAllDrivers($filters);
    }

    /**
     * Get driver by Firebase UID
     */
    public function getDriverById(string $firebaseUid): ?array
    {
        return $this->profileService->getDriverById($firebaseUid);
    }

    /**
     * Create new driver
     */
    public function createDriver(array $driverData): ?array
    {
        return $this->profileService->createDriver($driverData);
    }

    /**
     * Update driver
     */
    public function updateDriver(string $firebaseUid, array $driverData): bool
    {
        return $this->profileService->updateDriver($firebaseUid, $driverData);
    }

    /**
     * Delete driver
     */
    public function deleteDriver(string $firebaseUid): bool
    {
        return $this->profileService->deleteDriver($firebaseUid);
    }

    /**
     * Update driver status
     */
    public function updateDriverStatus(string $firebaseUid, string $status): bool
    {
        return $this->profileService->updateDriverStatus($firebaseUid, $status);
    }

    /**
     * Update driver verification status
     */
    public function updateDriverVerificationStatus(string $firebaseUid, string $verificationStatus, string $verifiedBy = null, string $notes = null): bool
    {
        return $this->profileService->updateDriverVerificationStatus($firebaseUid, $verificationStatus, $verifiedBy, $notes);
    }

    /**
     * Update driver location
     */
    public function updateDriverLocation(string $firebaseUid, float $latitude, float $longitude, string $address = null): bool
    {
        return $this->profileService->updateDriverLocation($firebaseUid, $latitude, $longitude, $address);
    }

    /**
     * Update driver availability
     */
    public function updateDriverAvailability(string $firebaseUid, string $availabilityStatus): bool
    {
        return $this->profileService->updateDriverAvailability($firebaseUid, $availabilityStatus);
    }

    /**
     * Get drivers near location
     */
    public function getDriversNearLocation(float $latitude, float $longitude, float $radiusKm = 10, array $filters = []): array
    {
        return $this->profileService->getDriversNearLocation($latitude, $longitude, $radiusKm, $filters);
    }

    /**
     * Search drivers
     */
    public function searchDrivers(string $query, int $limit = 50): array
    {
        return $this->profileService->searchDrivers($query, $limit);
    }

    /**
     * Get drivers by status
     */
    public function getDriversByStatus(string $status): array
    {
        return $this->profileService->getDriversByStatus($status);
    }

    /**
     * Get drivers by verification status
     */
    public function getDriversByVerificationStatus(string $verificationStatus): array
    {
        return $this->profileService->getDriversByVerificationStatus($verificationStatus);
    }

    /**
     * Get total drivers count
     */
    public function getTotalDriversCount(): int
    {
        return $this->profileService->getTotalDriversCount();
    }

    // ============ RIDE MANAGEMENT ============

    /**
     * Get driver's rides
     */
    public function getDriverRides(string $driverFirebaseUid, array $filters = []): array
    {
        return $this->rideService->getDriverRides($driverFirebaseUid, $filters);
    }

    /**
     * Get driver's ride statistics
     */
    public function getDriverRideStatistics(string $driverFirebaseUid): array
    {
        return $this->rideService->getDriverRideStatistics($driverFirebaseUid);
    }

    /**
     * Create ride
     */
    public function createRide(array $rideData): ?array
    {
        return $this->rideService->createRide($rideData);
    }

    /**
     * Update ride
     */
    public function updateRide(string $rideId, array $rideData): bool
    {
        return $this->rideService->updateRide($rideId, $rideData);
    }

    /**
     * Update ride status
     */
    public function updateRideStatus(string $rideId, string $status): bool
    {
        return $this->rideService->updateRideStatus($rideId, $status);
    }

    /**
     * Get ride by ID
     */
    public function getRideById(string $rideId): ?array
    {
        return $this->rideService->getRideById($rideId);
    }

    /**
     * Get active rides for driver
     */
    public function getActiveRidesForDriver(string $driverFirebaseUid): array
    {
        return $this->rideService->getActiveRidesForDriver($driverFirebaseUid);
    }

    /**
     * Complete ride
     */
    public function completeRide(string $rideId, array $completionData = []): bool
    {
        return $this->rideService->completeRide($rideId, $completionData);
    }

    /**
     * Cancel ride
     */
    public function cancelRide(string $rideId, string $reason = null): bool
    {
        return $this->rideService->cancelRide($rideId, $reason);
    }

    // ============ VEHICLE MANAGEMENT ============

    /**
     * Get driver's vehicles
     */
    public function getDriverVehicles(string $driverFirebaseUid): array
    {
        return $this->vehicleService->getDriverVehicles($driverFirebaseUid);
    }

    /**
     * Create vehicle for driver
     */
    public function createVehicle(array $vehicleData): ?array
    {
        return $this->vehicleService->createVehicle($vehicleData);
    }

    /**
     * Update vehicle
     */
    public function updateVehicle(string $vehicleId, array $vehicleData): bool
    {
        return $this->vehicleService->updateVehicle($vehicleId, $vehicleData);
    }

    /**
     * Delete vehicle
     */
    public function deleteVehicle(string $vehicleId): bool
    {
        return $this->vehicleService->deleteVehicle($vehicleId);
    }

    /**
     * Update vehicle verification status
     */
    public function updateVehicleVerificationStatus(string $vehicleId, string $verificationStatus): bool
    {
        return $this->vehicleService->updateVehicleVerificationStatus($vehicleId, $verificationStatus);
    }

    /**
     * Set primary vehicle
     */
    public function setPrimaryVehicle(string $driverFirebaseUid, string $vehicleId): bool
    {
        return $this->vehicleService->setPrimaryVehicle($driverFirebaseUid, $vehicleId);
    }

    /**
     * Get primary vehicle for driver
     */
    public function getPrimaryVehicleForDriver(string $driverFirebaseUid): ?array
    {
        return $this->vehicleService->getPrimaryVehicleForDriver($driverFirebaseUid);
    }

    // ============ DOCUMENT MANAGEMENT ============

    /**
     * Get driver documents
     */
    public function getDriverDocuments(string $driverFirebaseUid, array $filters = []): array
    {
        return $this->documentService->getDriverDocuments($driverFirebaseUid, $filters);
    }

    /**
     * Upload driver document
     */
    public function uploadDocument(string $driverFirebaseUid, $file, array $documentData): ?array
    {
        return $this->documentService->uploadDocument($driverFirebaseUid, $file, $documentData);
    }

    /**
     * Verify document
     */
    public function verifyDocument(string $documentId, string $verifiedBy = null, string $notes = null): bool
    {
        return $this->documentService->verifyDocument($documentId, $verifiedBy, $notes);
    }

    /**
     * Reject document
     */
    public function rejectDocument(string $documentId, string $rejectedBy = null, string $reason = null): bool
    {
        return $this->documentService->rejectDocument($documentId, $rejectedBy, $reason);
    }

    /**
     * Get pending documents
     */
    public function getPendingDocuments(): array
    {
        return $this->documentService->getPendingDocuments();
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments(): array
    {
        return $this->documentService->getExpiredDocuments();
    }

    /**
     * Get documents expiring soon
     */
    public function getDocumentsExpiringSoon(int $days = 30): array
    {
        return $this->documentService->getDocumentsExpiringSoon($days);
    }

    /**
     * Check if driver has all required documents
     */
    public function hasAllRequiredDocuments(string $driverFirebaseUid): bool
    {
        return $this->documentService->hasAllRequiredDocuments($driverFirebaseUid);
    }

    // ============ ACTIVITY MANAGEMENT ============

    /**
     * Get driver activities
     */
    public function getDriverActivities(string $driverFirebaseUid, array $filters = []): array
    {
        return $this->activityService->getDriverActivities($driverFirebaseUid, $filters);
    }

    /**
     * Create activity
     */
    public function createActivity(string $driverFirebaseUid, string $activityType, array $activityData = []): ?array
    {
        return $this->activityService->createActivity($driverFirebaseUid, $activityType, $activityData);
    }

    /**
     * Get recent activities for driver
     */
    public function getRecentActivitiesForDriver(string $driverFirebaseUid, int $days = 7, int $limit = 50): array
    {
        return $this->activityService->getRecentActivitiesForDriver($driverFirebaseUid, $days, $limit);
    }

    /**
     * Get unread activities for driver
     */
    public function getUnreadActivitiesForDriver(string $driverFirebaseUid): array
    {
        return $this->activityService->getUnreadActivitiesForDriver($driverFirebaseUid);
    }

    /**
     * Mark activity as read
     */
    public function markActivityAsRead(string $activityId): bool
    {
        return $this->activityService->markActivityAsRead($activityId);
    }

    // ============ STATISTICS AND ANALYTICS ============

    /**
     * Get driver statistics
     */
    public function getDriverStatistics(): array
    {
        return $this->statisticsService->getDriverStatistics();
    }

    /**
     * Get driver performance metrics
     */
    public function getDriverPerformanceMetrics(string $driverFirebaseUid): array
    {
        return $this->statisticsService->getDriverPerformanceMetrics($driverFirebaseUid);
    }

    /**
     * Get system analytics
     */
    public function getSystemAnalytics(): array
    {
        return $this->statisticsService->getSystemAnalytics();
    }

    // ============ BULK OPERATIONS ============

    /**
     * Perform bulk action on drivers
     */
    public function performBulkAction(string $action, array $driverIds): array
    {
        return $this->bulkService->performBulkAction($action, $driverIds);
    }

    /**
     * Bulk update driver statuses
     */
    public function bulkUpdateStatus(array $driverIds, string $status): array
    {
        return $this->bulkService->bulkUpdateStatus($driverIds, $status);
    }

    /**
     * Bulk verify drivers
     */
    public function bulkVerifyDrivers(array $driverIds, string $verifiedBy = null): array
    {
        return $this->bulkService->bulkVerifyDrivers($driverIds, $verifiedBy);
    }

    /**
     * Bulk delete drivers
     */
    public function bulkDeleteDrivers(array $driverIds): array
    {
        return $this->bulkService->bulkDeleteDrivers($driverIds);
    }

    /**
     * Bulk import drivers
     */
    public function bulkImportDrivers(array $driversData): array
    {
        return $this->bulkService->bulkImportDrivers($driversData);
    }

    /**
     * Export drivers
     */
    public function exportDrivers(array $filters = []): array
    {
        return $this->bulkService->exportDrivers($filters);
    }

    /**
     * Sync drivers from Firebase
     */
    public function syncFirebaseDrivers(): array
    {
        return $this->bulkService->syncFirebaseDrivers();
    }

    // ============ CONVENIENCE METHODS ============

    /**
     * Get driver dashboard data
     */
    public function getDriverDashboardData(string $driverFirebaseUid): array
    {
        try {
            Log::info('Getting driver dashboard data', ['firebase_uid' => $driverFirebaseUid]);

            $driver = $this->getDriverById($driverFirebaseUid);
            if (!$driver) {
                return ['error' => 'Driver not found'];
            }

            return [
                'driver' => $driver,
                'ride_statistics' => $this->getDriverRideStatistics($driverFirebaseUid),
                'active_rides' => $this->getActiveRidesForDriver($driverFirebaseUid),
                'recent_activities' => $this->getRecentActivitiesForDriver($driverFirebaseUid, 7, 10),
                'unread_activities_count' => count($this->getUnreadActivitiesForDriver($driverFirebaseUid)),
                'vehicles' => $this->getDriverVehicles($driverFirebaseUid),
                'primary_vehicle' => $this->getPrimaryVehicleForDriver($driverFirebaseUid),
                'documents_verified' => $this->hasAllRequiredDocuments($driverFirebaseUid),
                'performance_metrics' => $this->getDriverPerformanceMetrics($driverFirebaseUid)
            ];
        } catch (\Exception $e) {
            Log::error('Error getting driver dashboard data: ' . $e->getMessage());
            return ['error' => 'Failed to load dashboard data'];
        }
    }

    /**
     * Get admin dashboard data
     */
    public function getAdminDashboardData(): array
    {
        try {
            Log::info('Getting admin dashboard data');

            return [
                'statistics' => $this->getDriverStatistics(),
                'system_analytics' => $this->getSystemAnalytics(),
                'pending_documents' => count($this->getPendingDocuments()),
                'expired_documents' => count($this->getExpiredDocuments()),
                'expiring_soon_documents' => count($this->getDocumentsExpiringSoon()),
                'recent_registrations' => $this->getDriversByStatus('pending')
            ];
        } catch (\Exception $e) {
            Log::error('Error getting admin dashboard data: ' . $e->getMessage());
            return ['error' => 'Failed to load dashboard data'];
        }
    }

    /**
     * Get driver profile completion status
     */
    public function getDriverProfileCompletion(string $driverFirebaseUid): array
    {
        try {
            $driver = $this->getDriverById($driverFirebaseUid);
            if (!$driver) {
                return ['completion_percentage' => 0, 'missing_fields' => []];
            }

            $requiredFields = [
                'name', 'email', 'phone', 'date_of_birth', 
                'address', 'city', 'state', 'license_number'
            ];

            $completedFields = 0;
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!empty($driver[$field])) {
                    $completedFields++;
                } else {
                    $missingFields[] = $field;
                }
            }

            $profileCompletion = round(($completedFields / count($requiredFields)) * 100, 2);

            // Check documents
            $hasAllDocs = $this->hasAllRequiredDocuments($driverFirebaseUid);
            $vehicles = $this->getDriverVehicles($driverFirebaseUid);
            $hasVehicle = count($vehicles) > 0;

            return [
                'completion_percentage' => $profileCompletion,
                'missing_fields' => $missingFields,
                'has_all_documents' => $hasAllDocs,
                'has_vehicle' => $hasVehicle,
                'is_complete' => $profileCompletion === 100 && $hasAllDocs && $hasVehicle
            ];
        } catch (\Exception $e) {
            Log::error('Error getting driver profile completion: ' . $e->getMessage());
            return ['completion_percentage' => 0, 'missing_fields' => []];
        }
    }

    /**
     * Activate driver (complete onboarding)
     */
    public function activateDriver(string $driverFirebaseUid, string $activatedBy = null): array
    {
        try {
            Log::info('Activating driver', ['firebase_uid' => $driverFirebaseUid]);

            // Check if driver can be activated
            $profileCompletion = $this->getDriverProfileCompletion($driverFirebaseUid);
            
            if (!$profileCompletion['is_complete']) {
                return [
                    'success' => false,
                    'message' => 'Driver profile is not complete',
                    'completion_status' => $profileCompletion
                ];
            }

            // Update driver status and verification
            $statusUpdated = $this->updateDriverStatus($driverFirebaseUid, 'active');
            $verificationUpdated = $this->updateDriverVerificationStatus(
                $driverFirebaseUid, 
                'verified', 
                $activatedBy, 
                'Driver activated after completing onboarding'
            );

            if ($statusUpdated && $verificationUpdated) {
                // Create welcome activity
                $this->createActivity($driverFirebaseUid, 'profile_update', [
                    'title' => 'Welcome to the Platform!',
                    'description' => 'Your driver account has been activated. You can now start accepting rides.',
                    'priority' => 'high'
                ]);

                return [
                    'success' => true,
                    'message' => 'Driver activated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to activate driver'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error activating driver: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error activating driver: ' . $e->getMessage()
            ];
        }
    }
}