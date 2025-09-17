<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\DriverActivity;
use Illuminate\Support\Facades\Log;

class DriverProfileService extends BaseService
{
    protected $collection = 'drivers';

    /**
     * Get all drivers with optional filters
     */
    public function getAllDrivers(array $filters = []): array
    {
        try {
            Log::info('DriverProfileService: Getting all drivers with filters', $filters);
            
            $drivers = $this->getAllDocuments($filters);
            
            Log::info('DriverProfileService: Retrieved drivers count: ' . count($drivers));
            
            // Sanitize driver data
            $sanitizedDrivers = array_map([$this, 'sanitizeDriverData'], $drivers);
            
            Log::info('DriverProfileService: Sanitized drivers count: ' . count($sanitizedDrivers));
            
            return $sanitizedDrivers;
        } catch (\Exception $e) {
            Log::error('Error getting all drivers: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Enhanced search matching for drivers - OVERRIDE BASE METHOD
     */
    protected function matchesSearch(array $document, string $search): bool
    {
        // Driver-specific searchable fields
        $searchableFields = [
            'name', 
            'email', 
            'phone', 
            'license_number', 
            'firebase_uid',
            'city',
            'state'
        ];
        
        Log::debug('DriverProfileService: Checking search match', [
            'search' => $search,
            'document_fields' => array_keys($document)
        ]);
        
        foreach ($searchableFields as $field) {
            if (isset($document[$field]) && !empty($document[$field])) {
                if (stripos($document[$field], $search) !== false) {
                    Log::debug('DriverProfileService: Search match found', [
                        'field' => $field,
                        'value' => $document[$field],
                        'search' => $search
                    ]);
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get driver by Firebase UID
     */
    public function getDriverById(string $firebaseUid): ?array
    {
        try {
            Log::info('Getting driver by Firebase UID', ['firebase_uid' => $firebaseUid]);
            
            $driver = $this->getDocumentById($firebaseUid);
            
            if ($driver) {
                return $this->sanitizeDriverData($driver);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error getting driver by ID: ' . $e->getMessage(), [
                'firebase_uid' => $firebaseUid,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Create new driver
     */
    public function createDriver(array $driverData): ?array
    {
        try {
            Log::info('Creating new driver', ['email' => $driverData['email'] ?? 'unknown']);
            
            // Validate required fields
            if (empty($driverData['firebase_uid']) || empty($driverData['email'])) {
                Log::warning('Cannot create driver without Firebase UID or email');
                return null;
            }
            
            // Set defaults
            $driverData = $this->setDriverDefaults($driverData);
            
            $result = $this->createDocument($driverData, $driverData['firebase_uid']);
            
            if ($result) {
                $result = $this->sanitizeDriverData($result);
                Log::info('Driver created successfully', ['firebase_uid' => $result['firebase_uid'] ?? 'unknown']);
                
                // Create initial activity
                $this->createDriverActivity($driverData['firebase_uid'], DriverActivity::TYPE_PROFILE_UPDATE, [
                    'title' => 'Driver Profile Created',
                    'description' => 'New driver profile has been created and is pending verification.'
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error creating driver: ' . $e->getMessage(), [
                'driver_data' => $driverData,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Update driver
     */
 public function updateDriver(string $firebaseUid, array $driverData): bool
{
    try {
        Log::info('DriverProfileService: updateDriver called', [
            'firebase_uid' => $firebaseUid,
            'fields' => array_keys($driverData)
        ]);
        
       
        $result = $this->updateDocument($firebaseUid, $driverData);
        
        if ($result) {
            Log::info('DriverProfileService: Update successful', [
                'firebase_uid' => $firebaseUid
            ]);
            
            // Create activity
            $this->createDriverActivity($firebaseUid, DriverActivity::TYPE_PROFILE_UPDATE, [
                'title' => 'Profile Updated',
                'description' => 'Driver profile information has been updated.'
            ]);
        } else {
            Log::error('DriverProfileService: Update failed', [
                'firebase_uid' => $firebaseUid
            ]);
        }
        
        return $result;
    } catch (\Exception $e) {
        Log::error('DriverProfileService: Error in updateDriver', [
            'firebase_uid' => $firebaseUid,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}






    /**
     * Delete driver
     */
    public function deleteDriver(string $firebaseUid): bool
    {
        try {
            Log::info('Deleting driver', ['firebase_uid' => $firebaseUid]);
            
            $result = $this->deleteDocument($firebaseUid);
            
            if ($result) {
                Log::info('Driver deleted successfully', ['firebase_uid' => $firebaseUid]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error deleting driver: ' . $e->getMessage(), [
                'firebase_uid' => $firebaseUid,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update driver status
     */
   public function updateDriverStatus(string $firebaseUid, string $status): bool
{
    try {
        Log::info('DriverProfileService: Updating driver status - START', [
            'firebase_uid' => $firebaseUid, 
            'status' => $status,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        $updateData = ['status' => $status];
        
        if ($status === Driver::STATUS_ACTIVE) {
            $updateData['verification_date'] = now()->toDateTimeString();
            Log::info('Adding verification_date for active status', ['firebase_uid' => $firebaseUid]);
        }
        
        Log::info('About to update document in Firestore', [
            'firebase_uid' => $firebaseUid,
            'update_data' => $updateData
        ]);
        
        $result = $this->updateDocument($firebaseUid, $updateData);
        
        Log::info('Firestore update result', [
            'firebase_uid' => $firebaseUid,
            'result' => $result ? 'success' : 'failed'
        ]);
        
        if ($result) {
            // Create activity
            Log::info('Creating driver activity for status change', ['firebase_uid' => $firebaseUid]);
            
            $this->createDriverActivity($firebaseUid, DriverActivity::TYPE_STATUS_CHANGE, [
                'title' => 'Status Changed',
                'description' => "Driver status changed to: " . ucfirst($status),
                'metadata' => ['new_status' => $status]
            ]);
            
            Log::info('DriverProfileService: Driver status updated successfully', [
                'firebase_uid' => $firebaseUid,
                'new_status' => $status
            ]);
        } else {
            Log::error('DriverProfileService: Failed to update driver status in Firestore', [
                'firebase_uid' => $firebaseUid,
                'status' => $status
            ]);
        }
        
        return $result;
    } catch (\Exception $e) {
        Log::error('DriverProfileService: Error updating driver status', [
            'firebase_uid' => $firebaseUid,
            'status' => $status,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}
    /**
     * Update driver verification status
     */
    public function updateDriverVerificationStatus(string $firebaseUid, string $verificationStatus, string $verifiedBy = null, string $notes = null): bool
    {
        try {
            Log::info('Updating driver verification status', [
                'firebase_uid' => $firebaseUid, 
                'verification_status' => $verificationStatus
            ]);
            
            $updateData = [
                'verification_status' => $verificationStatus,
                'verification_date' => now()->toDateTimeString()
            ];
            
            if ($verifiedBy) {
                $updateData['verified_by'] = $verifiedBy;
            }
            
            if ($notes) {
                $updateData['verification_notes'] = $notes;
            }
            
            $result = $this->updateDocument($firebaseUid, $updateData);
            
            if ($result) {
                // Create activity
                $this->createDriverActivity($firebaseUid, DriverActivity::TYPE_STATUS_CHANGE, [
                    'title' => 'Verification Status Changed',
                    'description' => "Driver verification status changed to: " . ucfirst($verificationStatus),
                    'metadata' => [
                        'new_verification_status' => $verificationStatus,
                        'verified_by' => $verifiedBy,
                        'notes' => $notes
                    ]
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error updating driver verification status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update driver location
     */
    public function updateDriverLocation(string $firebaseUid, float $latitude, float $longitude, string $address = null): bool
    {
        try {
            Log::info('Updating driver location', [
                'firebase_uid' => $firebaseUid,
                'lat' => $latitude,
                'lng' => $longitude
            ]);

            $updateData = [
                'current_location_lat' => $latitude,
                'current_location_lng' => $longitude,
                'last_location_update' => now()->toDateTimeString(),
                'last_active' => now()->toDateTimeString()
            ];

            if ($address) {
                $updateData['current_address'] = $address;
            }

            $result = $this->updateDocument($firebaseUid, $updateData);

            if ($result) {
                // Create location update activity (only for significant moves)
                $this->createDriverActivity($firebaseUid, DriverActivity::TYPE_LOCATION_UPDATE, [
                    'title' => 'Location Updated',
                    'description' => 'Driver location has been updated.',
                    'location_latitude' => $latitude,
                    'location_longitude' => $longitude,
                    'location_address' => $address,
                    'priority' => DriverActivity::PRIORITY_LOW
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error updating driver location: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update driver availability status
     */
    public function updateDriverAvailability(string $firebaseUid, string $availabilityStatus): bool
    {
        try {
            Log::info('Updating driver availability', [
                'firebase_uid' => $firebaseUid,
                'availability_status' => $availabilityStatus
            ]);

            $updateData = [
                'availability_status' => $availabilityStatus,
                'last_active' => now()->toDateTimeString()
            ];

            $result = $this->updateDocument($firebaseUid, $updateData);

            if ($result) {
                $this->createDriverActivity($firebaseUid, DriverActivity::TYPE_STATUS_CHANGE, [
                    'title' => 'Availability Changed',
                    'description' => "Driver availability changed to: " . ucfirst(str_replace('_', ' ', $availabilityStatus)),
                    'metadata' => ['new_availability_status' => $availabilityStatus]
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error updating driver availability: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get drivers near location
     */
    public function getDriversNearLocation(float $latitude, float $longitude, float $radiusKm = 10, array $filters = []): array
    {
        try {
            Log::info('Getting drivers near location', [
                'lat' => $latitude,
                'lng' => $longitude,
                'radius_km' => $radiusKm
            ]);

            // Get all available drivers
            $allDrivers = $this->getAllDrivers(array_merge($filters, [
                'status' => Driver::STATUS_ACTIVE,
                'limit' => 1000
            ]));

            $nearbyDrivers = [];

            foreach ($allDrivers as $driver) {
                if (!isset($driver['current_location_lat']) || !isset($driver['current_location_lng'])) {
                    continue;
                }

                // Calculate distance using Haversine formula
                $distance = $this->calculateDistance(
                    $latitude,
                    $longitude,
                    $driver['current_location_lat'],
                    $driver['current_location_lng']
                );

                if ($distance <= $radiusKm) {
                    $driver['distance_km'] = round($distance, 2);
                    $nearbyDrivers[] = $driver;
                }
            }

            // Sort by distance
            usort($nearbyDrivers, function($a, $b) {
                return $a['distance_km'] <=> $b['distance_km'];
            });

            Log::info('Found nearby drivers', ['count' => count($nearbyDrivers)]);
            return $nearbyDrivers;
        } catch (\Exception $e) {
            Log::error('Error getting drivers near location: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search drivers
     */
    public function searchDrivers(string $query, int $limit = 50): array
    {
        try {
            Log::info('Searching drivers', ['query' => $query]);
            
            return $this->getAllDrivers([
                'search' => $query,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching drivers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get drivers by status
     */
    public function getDriversByStatus(string $status): array
    {
        return $this->getAllDrivers(['status' => $status, 'limit' => 1000]);
    }

    /**
     * Get drivers by verification status
     */
    public function getDriversByVerificationStatus(string $verificationStatus): array
    {
        return $this->getAllDrivers(['verification_status' => $verificationStatus, 'limit' => 1000]);
    }

    /**
     * Get total drivers count
     */
    public function getTotalDriversCount(): int
    {
        return $this->countDocuments();
    }

    /**
     * Set driver defaults
     */
    private function setDriverDefaults(array $driverData): array
    {
        return array_merge([
            'status' => Driver::STATUS_PENDING,
            'verification_status' => Driver::VERIFICATION_PENDING,
            'availability_status' => Driver::AVAILABILITY_OFFLINE,
            'rating' => 5.0,
            'total_rides' => 0,
            'completed_rides' => 0,
            'cancelled_rides' => 0,
            'total_earnings' => 0.00,
            'join_date' => now()->toDateTimeString()
        ], $driverData);
    }

    /**
     * Sanitize driver data
     */
    private function sanitizeDriverData(array $driver): array
    {
        return [
            'id' => $driver['id'] ?? 'unknown',
            'firebase_uid' => $driver['firebase_uid'] ?? $driver['id'] ?? 'unknown',
            'name' => !empty($driver['name']) ? trim($driver['name']) : 'Unknown Driver',
            'email' => !empty($driver['email']) ? trim(strtolower($driver['email'])) : 'No Email',
            'phone' => $driver['phone'] ?? null,
            'photo_url' => $driver['photo_url'] ?? null,
            'date_of_birth' => $driver['date_of_birth'] ?? null,
            'gender' => $driver['gender'] ?? null,
            'address' => $driver['address'] ?? null,
            'city' => $driver['city'] ?? null,
            'state' => $driver['state'] ?? null,
            'postal_code' => $driver['postal_code'] ?? null,
            'country' => $driver['country'] ?? null,
            'license_number' => $driver['license_number'] ?? null,
            'license_expiry' => $driver['license_expiry'] ?? null,
            'status' => $driver['status'] ?? Driver::STATUS_PENDING,
            'verification_status' => $driver['verification_status'] ?? Driver::VERIFICATION_PENDING,
            'verification_date' => $driver['verification_date'] ?? null,
            'availability_status' => $driver['availability_status'] ?? Driver::AVAILABILITY_OFFLINE,
            'rating' => isset($driver['rating']) ? (float) $driver['rating'] : 5.0,
            'total_rides' => isset($driver['total_rides']) ? (int) $driver['total_rides'] : 0,
            'completed_rides' => isset($driver['completed_rides']) ? (int) $driver['completed_rides'] : 0,
            'cancelled_rides' => isset($driver['cancelled_rides']) ? (int) $driver['cancelled_rides'] : 0,
            'total_earnings' => isset($driver['total_earnings']) ? (float) $driver['total_earnings'] : 0.00,
            'current_location_lat' => isset($driver['current_location_lat']) ? (float) $driver['current_location_lat'] : null,
            'current_location_lng' => isset($driver['current_location_lng']) ? (float) $driver['current_location_lng'] : null,
            'last_location_update' => $driver['last_location_update'] ?? null,
            'join_date' => $driver['join_date'] ?? $driver['created_at'] ?? null,
            'last_active' => $driver['last_active'] ?? null,
            'created_at' => $driver['created_at'] ?? null,
            'updated_at' => $driver['updated_at'] ?? null
        ];
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
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
}