<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\DriverActivity;
use Illuminate\Support\Facades\Log;

class DriverVehicleService extends BaseService
{
    protected $collection = 'vehicles';

    /**
     * Get driver's vehicles
     */
    public function getDriverVehicles(string $driverFirebaseUid): array
    {
        try {
            Log::info('Getting vehicles for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $allVehicles = $this->getAllDocuments(['limit' => 1000]);
            
            return $this->filterByField($allVehicles, 'driver_firebase_uid', $driverFirebaseUid);
        } catch (\Exception $e) {
            Log::error('Error getting driver vehicles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create vehicle for driver
     */
    public function createVehicle(array $vehicleData): ?array
    {
        try {
            Log::info('Creating vehicle', ['driver_uid' => $vehicleData['driver_firebase_uid'] ?? 'unknown']);
            
            $vehicleData = $this->setVehicleDefaults($vehicleData);
            
            $result = $this->createDocument($vehicleData);
            
            if ($result && isset($vehicleData['driver_firebase_uid'])) {
                // Create activity
                $this->createDriverActivity($vehicleData['driver_firebase_uid'], DriverActivity::TYPE_VEHICLE_UPDATE, [
                    'title' => 'Vehicle Added',
                    'description' => "New vehicle added: {$vehicleData['year']} {$vehicleData['make']} {$vehicleData['model']}",
                    'metadata' => ['vehicle_id' => $result['id'] ?? null]
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error creating vehicle: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update vehicle
     */
    public function updateVehicle(string $vehicleId, array $vehicleData): bool
    {
        try {
            Log::info('Updating vehicle', ['vehicle_id' => $vehicleId]);
            
            $result = $this->updateDocument($vehicleId, $vehicleData);
            
            if ($result && isset($vehicleData['driver_firebase_uid'])) {
                // Create activity
                $this->createDriverActivity($vehicleData['driver_firebase_uid'], DriverActivity::TYPE_VEHICLE_UPDATE, [
                    'title' => 'Vehicle Updated',
                    'description' => 'Vehicle information has been updated.',
                    'metadata' => ['vehicle_id' => $vehicleId]
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error updating vehicle: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete vehicle
     */
    public function deleteVehicle(string $vehicleId): bool
    {
        try {
            Log::info('Deleting vehicle', ['vehicle_id' => $vehicleId]);
            
            // Get vehicle data before deletion for activity log
            $vehicle = parent::getDocumentById($vehicleId);
            
            $result = $this->deleteDocument($vehicleId);
            
            if ($result && $vehicle && isset($vehicle['driver_firebase_uid'])) {
                // Create activity
                $this->createDriverActivity($vehicle['driver_firebase_uid'], DriverActivity::TYPE_VEHICLE_UPDATE, [
                    'title' => 'Vehicle Removed',
                    'description' => "Vehicle removed: {$vehicle['year']} {$vehicle['make']} {$vehicle['model']}",
                    'metadata' => ['vehicle_id' => $vehicleId]
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error deleting vehicle: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get vehicle by ID
     */
    public function getVehicleById(string $vehicleId): ?array
    {
        return $this->getDocumentById($vehicleId);
    }

    /**
     * Update vehicle status
     */
    public function updateVehicleStatus(string $vehicleId, string $status): bool
    {
        try {
            Log::info('Updating vehicle status', ['vehicle_id' => $vehicleId, 'status' => $status]);
            
            $updateData = ['status' => $status];
            
            return $this->updateDocument($vehicleId, $updateData);
        } catch (\Exception $e) {
            Log::error('Error updating vehicle status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update vehicle verification status
     */
    public function updateVehicleVerificationStatus(string $vehicleId, string $verificationStatus): bool
    {
        try {
            Log::info('Updating vehicle verification status', [
                'vehicle_id' => $vehicleId, 
                'verification_status' => $verificationStatus
            ]);
            
            $updateData = [
                'verification_status' => $verificationStatus,
                'verification_date' => now()->toDateTimeString()
            ];
            
            $result = $this->updateDocument($vehicleId, $updateData);
            
            if ($result) {
                // Get vehicle data for activity
                $vehicle = $this->getDocumentById($vehicleId);
                if ($vehicle && isset($vehicle['driver_firebase_uid'])) {
                    $this->createDriverActivity($vehicle['driver_firebase_uid'], DriverActivity::TYPE_VEHICLE_UPDATE, [
                        'title' => 'Vehicle Verification Updated',
                        'description' => "Vehicle verification status changed to: " . ucfirst($verificationStatus),
                        'metadata' => [
                            'vehicle_id' => $vehicleId,
                            'new_verification_status' => $verificationStatus
                        ]
                    ]);
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error updating vehicle verification status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get vehicles by status
     */
    public function getVehiclesByStatus(string $status): array
    {
        return $this->getAllDocuments(['status' => $status, 'limit' => 1000]);
    }

    /**
     * Get vehicles by verification status
     */
    public function getVehiclesByVerificationStatus(string $verificationStatus): array
    {
        return $this->getAllDocuments(['verification_status' => $verificationStatus, 'limit' => 1000]);
    }

    /**
     * Get active vehicles for driver
     */
    public function getActiveVehiclesForDriver(string $driverFirebaseUid): array
    {
        $vehicles = $this->getDriverVehicles($driverFirebaseUid);
        
        return array_filter($vehicles, function($vehicle) {
            return ($vehicle['status'] ?? '') === Vehicle::STATUS_ACTIVE;
        });
    }

    /**
     * Get verified vehicles for driver
     */
    public function getVerifiedVehiclesForDriver(string $driverFirebaseUid): array
    {
        $vehicles = $this->getDriverVehicles($driverFirebaseUid);
        
        return array_filter($vehicles, function($vehicle) {
            return ($vehicle['verification_status'] ?? '') === Vehicle::VERIFICATION_VERIFIED;
        });
    }

    /**
     * Set default vehicle as primary
     */
    public function setPrimaryVehicle(string $driverFirebaseUid, string $vehicleId): bool
    {
        try {
            // First, remove primary status from all driver's vehicles
            $vehicles = $this->getDriverVehicles($driverFirebaseUid);
            
            foreach ($vehicles as $vehicle) {
                if (isset($vehicle['is_primary']) && $vehicle['is_primary']) {
                    $this->updateDocument($vehicle['id'], ['is_primary' => false]);
                }
            }
            
            // Set the selected vehicle as primary
            return $this->updateDocument($vehicleId, ['is_primary' => true]);
        } catch (\Exception $e) {
            Log::error('Error setting primary vehicle: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get primary vehicle for driver
     */
    public function getPrimaryVehicleForDriver(string $driverFirebaseUid): ?array
    {
        $vehicles = $this->getDriverVehicles($driverFirebaseUid);
        
        foreach ($vehicles as $vehicle) {
            if (isset($vehicle['is_primary']) && $vehicle['is_primary']) {
                return $vehicle;
            }
        }
        
        // Return first active vehicle if no primary set
        foreach ($vehicles as $vehicle) {
            if (($vehicle['status'] ?? '') === Vehicle::STATUS_ACTIVE) {
                return $vehicle;
            }
        }
        
        return null;
    }

    /**
     * Set vehicle defaults
     */
    private function setVehicleDefaults(array $vehicleData): array
    {
        return array_merge([
            'status' => Vehicle::STATUS_ACTIVE,
            'verification_status' => Vehicle::VERIFICATION_PENDING,
            'is_primary' => false,
            'mileage' => 0,
            'seats' => 4
        ], $vehicleData);
    }

    /**
     * Create driver activity
     */
    private function createDriverActivity(string $firebaseUid, string $type, array $data): void
    {
        try {
            DriverActivity::createActivity($firebaseUid, $type, $data);
        } catch (\Exception $e) {
            Log::warning('Failed to create driver activity', [
                'firebase_uid' => $firebaseUid,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if document matches search query
     */
    protected function matchesSearch(array $document, string $search): bool
    {
        $searchableFields = ['make', 'model', 'year', 'license_plate', 'vin', 'color'];
        
        foreach ($searchableFields as $field) {
            if (isset($document[$field]) && stripos($document[$field], $search) !== false) {
                return true;
            }
        }
        
        return false;
    }
}