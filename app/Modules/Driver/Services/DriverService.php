<?php
namespace App\Modules\Driver\Services;

use App\Services\FirestoreService;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\Vehicle;
use App\Modules\Driver\Models\Ride;
use App\Modules\Driver\Models\DriverActivity;
use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Driver\Models\DriverLicense;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DriverService
{
    protected $firestoreService;
    protected $collections = [
        'drivers' => 'drivers',
        'vehicles' => 'vehicles',
        'rides' => 'rides',
        'activities' => 'driver_activities',
        'documents' => 'driver_documents',
        'licenses' => 'driver_licenses'
    ];

    public function __construct(FirestoreService $firestoreService)
    {
        $this->firestoreService = $firestoreService;
    }

    // ============ DRIVER MANAGEMENT ============

    /**
     * Get all drivers with optional filters
     */
    public function getAllDrivers(array $filters = []): array
    {
        try {
            Log::info('Getting all drivers with filters', $filters);
            
            $limit = $filters['limit'] ?? 50;
            $drivers = $this->firestoreService->collection($this->collections['drivers'])->getAll($limit);
            
            // Sanitize driver data
            $drivers = array_map([$this, 'sanitizeDriverData'], $drivers);
            
            // Apply filters
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $drivers = array_filter($drivers, function($driver) use ($search) {
                    return stripos($driver['name'] ?? '', $search) !== false || 
                           stripos($driver['email'] ?? '', $search) !== false ||
                           stripos($driver['phone'] ?? '', $search) !== false ||
                           stripos($driver['license_number'] ?? '', $search) !== false;
                });
            }

            if (!empty($filters['status'])) {
                $drivers = array_filter($drivers, function($driver) use ($filters) {
                    return ($driver['status'] ?? 'active') === $filters['status'];
                });
            }

            if (!empty($filters['verification_status'])) {
                $drivers = array_filter($drivers, function($driver) use ($filters) {
                    return ($driver['verification_status'] ?? 'pending') === $filters['verification_status'];
                });
            }

            if (!empty($filters['availability_status'])) {
                $drivers = array_filter($drivers, function($driver) use ($filters) {
                    return ($driver['availability_status'] ?? 'offline') === $filters['availability_status'];
                });
            }

            Log::debug('Retrieved drivers', ['count' => count($drivers)]);
            return array_values($drivers);

        } catch (\Exception $e) {
            Log::error('Error getting all drivers: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get driver by Firebase UID
     */
    public function getDriverById(string $firebaseUid): ?array
    {
        try {
            Log::info('Getting driver by Firebase UID', ['firebase_uid' => $firebaseUid]);
            
            $driver = $this->firestoreService->collection($this->collections['drivers'])->find($firebaseUid);
            
            if ($driver) {
                $driver = $this->sanitizeDriverData($driver);
                Log::debug('Driver found and sanitized', ['firebase_uid' => $firebaseUid]);
            }
            
            return $driver;

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
            $driverData['status'] = $driverData['status'] ?? Driver::STATUS_PENDING;
            $driverData['verification_status'] = $driverData['verification_status'] ?? Driver::VERIFICATION_PENDING;
            $driverData['availability_status'] = $driverData['availability_status'] ?? Driver::AVAILABILITY_OFFLINE;
            $driverData['rating'] = $driverData['rating'] ?? 5.0;
            $driverData['total_rides'] = $driverData['total_rides'] ?? 0;
            $driverData['completed_rides'] = $driverData['completed_rides'] ?? 0;
            $driverData['cancelled_rides'] = $driverData['cancelled_rides'] ?? 0;
            $driverData['total_earnings'] = $driverData['total_earnings'] ?? 0.00;
            $driverData['join_date'] = $driverData['join_date'] ?? now()->toDateTimeString();
            $driverData['created_at'] = now()->toDateTimeString();
            $driverData['updated_at'] = now()->toDateTimeString();
            
            $result = $this->firestoreService->collection($this->collections['drivers'])->create($driverData, $driverData['firebase_uid']);
            
            if ($result) {
                $result = $this->sanitizeDriverData($result);
                Log::info('Driver created successfully', ['firebase_uid' => $result['firebase_uid'] ?? 'unknown']);
                
                // Create initial activity
                DriverActivity::createActivity($driverData['firebase_uid'], DriverActivity::TYPE_PROFILE_UPDATE, [
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
            Log::info('Updating driver', ['firebase_uid' => $firebaseUid]);
            
            $driverData['updated_at'] = now()->toDateTimeString();
            
            $result = $this->firestoreService->collection($this->collections['drivers'])->update($firebaseUid, $driverData);
            
            if ($result) {
                Log::info('Driver updated successfully', ['firebase_uid' => $firebaseUid]);
                
                // Create activity
                DriverActivity::createActivity($firebaseUid, DriverActivity::TYPE_PROFILE_UPDATE, [
                    'title' => 'Profile Updated',
                    'description' => 'Driver profile information has been updated.'
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error updating driver: ' . $e->getMessage(), [
                'firebase_uid' => $firebaseUid,
                'driver_data' => $driverData,
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
            
            // Delete related data
            $this->deleteDriverRelatedData($firebaseUid);
            
            $result = $this->firestoreService->collection($this->collections['drivers'])->delete($firebaseUid);
            
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
            Log::info('Updating driver status', ['firebase_uid' => $firebaseUid, 'status' => $status]);
            
            $updateData = [
                'status' => $status,
                'updated_at' => now()->toDateTimeString()
            ];
            
            if ($status === Driver::STATUS_ACTIVE) {
                $updateData['verification_date'] = now()->toDateTimeString();
            }
            
            $result = $this->firestoreService->collection($this->collections['drivers'])->update($firebaseUid, $updateData);
            
            if ($result) {
                // Create activity
                DriverActivity::createActivity($firebaseUid, DriverActivity::TYPE_STATUS_CHANGE, [
                    'title' => 'Status Changed',
                    'description' => "Driver status changed to: " . ucfirst($status),
                    'metadata' => ['new_status' => $status]
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error updating driver status: ' . $e->getMessage());
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
                'verification_date' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];
            
            if ($verifiedBy) {
                $updateData['verified_by'] = $verifiedBy;
            }
            
            if ($notes) {
                $updateData['verification_notes'] = $notes;
            }
            
            $result = $this->firestoreService->collection($this->collections['drivers'])->update($firebaseUid, $updateData);
            
            if ($result) {
                // Create activity
                DriverActivity::createActivity($firebaseUid, DriverActivity::TYPE_STATUS_CHANGE, [
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

    // ============ VEHICLE MANAGEMENT ============

    /**
     * Get driver's vehicles
     */
    public function getDriverVehicles(string $driverFirebaseUid): array
    {
        try {
            Log::info('Getting vehicles for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $vehicles = $this->firestoreService->collection($this->collections['vehicles'])->getAll();
            
            // Filter by driver
            $driverVehicles = array_filter($vehicles, function($vehicle) use ($driverFirebaseUid) {
                return ($vehicle['driver_firebase_uid'] ?? '') === $driverFirebaseUid;
            });
            
            return array_values($driverVehicles);

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
            
            $vehicleData['status'] = $vehicleData['status'] ?? Vehicle::STATUS_ACTIVE;
            $vehicleData['verification_status'] = $vehicleData['verification_status'] ?? Vehicle::VERIFICATION_PENDING;
            $vehicleData['created_at'] = now()->toDateTimeString();
            $vehicleData['updated_at'] = now()->toDateTimeString();
            
            $result = $this->firestoreService->collection($this->collections['vehicles'])->create($vehicleData);
            
            if ($result && isset($vehicleData['driver_firebase_uid'])) {
                // Create activity
                DriverActivity::createActivity($vehicleData['driver_firebase_uid'], DriverActivity::TYPE_VEHICLE_UPDATE, [
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
            
            $vehicleData['updated_at'] = now()->toDateTimeString();
            
            $result = $this->firestoreService->collection($this->collections['vehicles'])->update($vehicleId, $vehicleData);
            
            if ($result && isset($vehicleData['driver_firebase_uid'])) {
                // Create activity
                DriverActivity::createActivity($vehicleData['driver_firebase_uid'], DriverActivity::TYPE_VEHICLE_UPDATE, [
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

    // ============ RIDE MANAGEMENT ============

    /**
     * Get driver's rides
     */
    public function getDriverRides(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('Getting rides for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $rides = $this->firestoreService->collection($this->collections['rides'])->getAll();
            
            // Filter by driver
            $driverRides = array_filter($rides, function($ride) use ($driverFirebaseUid) {
                return ($ride['driver_firebase_uid'] ?? '') === $driverFirebaseUid;
            });
            
            // Apply additional filters
            if (!empty($filters['status'])) {
                $driverRides = array_filter($driverRides, function($ride) use ($filters) {
                    return ($ride['status'] ?? '') === $filters['status'];
                });
            }
            
            if (!empty($filters['date_from'])) {
                $dateFrom = Carbon::parse($filters['date_from']);
                $driverRides = array_filter($driverRides, function($ride) use ($dateFrom) {
                    $rideDate = Carbon::parse($ride['created_at'] ?? now());
                    return $rideDate->gte($dateFrom);
                });
            }
            
            if (!empty($filters['date_to'])) {
                $dateTo = Carbon::parse($filters['date_to']);
                $driverRides = array_filter($driverRides, function($ride) use ($dateTo) {
                    $rideDate = Carbon::parse($ride['created_at'] ?? now());
                    return $rideDate->lte($dateTo);
                });
            }
            
            return array_values($driverRides);

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
            $rides = $this->getDriverRides($driverFirebaseUid);
            
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
                'today_rides' => 0,
                'this_week_rides' => 0,
                'this_month_rides' => 0
            ];
            
            $totalRating = 0;
            $ratedRides = 0;
            $today = now()->startOfDay();
            $weekStart = now()->startOfWeek();
            $monthStart = now()->startOfMonth();
            
            foreach ($rides as $ride) {
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
            
            // Calculate rates
            if ($stats['total_rides'] > 0) {
                $stats['completion_rate'] = round(($stats['completed_rides'] / $stats['total_rides']) * 100, 2);
                $stats['cancellation_rate'] = round(($stats['cancelled_rides'] / $stats['total_rides']) * 100, 2);
            }
            
            // Calculate average rating
            if ($ratedRides > 0) {
                $stats['average_rating'] = round($totalRating / $ratedRides, 2);
            }
            
            return $stats;

        } catch (\Exception $e) {
            Log::error('Error getting driver ride statistics: ' . $e->getMessage());
            return [];
        }
    }

    // ============ ACTIVITY MANAGEMENT ============

    /**
     * Get driver activities
     */
    public function getDriverActivities(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('Getting activities for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $activities = $this->firestoreService->collection($this->collections['activities'])->getAll();
            
            // Filter by driver
            $driverActivities = array_filter($activities, function($activity) use ($driverFirebaseUid) {
                return ($activity['driver_firebase_uid'] ?? '') === $driverFirebaseUid;
            });
            
            // Apply filters
            if (!empty($filters['category'])) {
                $driverActivities = array_filter($driverActivities, function($activity) use ($filters) {
                    return ($activity['activity_category'] ?? '') === $filters['category'];
                });
            }
            
            if (!empty($filters['type'])) {
                $driverActivities = array_filter($driverActivities, function($activity) use ($filters) {
                    return ($activity['activity_type'] ?? '') === $filters['type'];
                });
            }
            
            // Sort by created_at desc
            usort($driverActivities, function($a, $b) {
                $dateA = Carbon::parse($a['created_at'] ?? now());
                $dateB = Carbon::parse($b['created_at'] ?? now());
                return $dateB->timestamp - $dateA->timestamp;
            });
            
            return array_values($driverActivities);

        } catch (\Exception $e) {
            Log::error('Error getting driver activities: ' . $e->getMessage());
            return [];
        }
    }

    // ============ DOCUMENT MANAGEMENT ============

    /**
     * Get driver documents
     */
    public function getDriverDocuments(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('Getting documents for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $documents = $this->firestoreService->collection($this->collections['documents'])->getAll();
            
            // Filter by driver
            $driverDocuments = array_filter($documents, function($document) use ($driverFirebaseUid) {
                return ($document['driver_firebase_uid'] ?? '') === $driverFirebaseUid;
            });
            
            // Apply filters
            if (!empty($filters['document_type'])) {
                $driverDocuments = array_filter($driverDocuments, function($document) use ($filters) {
                    return ($document['document_type'] ?? '') === $filters['document_type'];
                });
            }
            
            if (!empty($filters['verification_status'])) {
                $driverDocuments = array_filter($driverDocuments, function($document) use ($filters) {
                    return ($document['verification_status'] ?? '') === $filters['verification_status'];
                });
            }
            
            return array_values($driverDocuments);

        } catch (\Exception $e) {
            Log::error('Error getting driver documents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Upload driver document
     */
    public function uploadDocument(string $driverFirebaseUid, UploadedFile $file, array $documentData): ?array
    {
        try {
            Log::info('Uploading document for driver', [
                'firebase_uid' => $driverFirebaseUid,
                'document_type' => $documentData['document_type'] ?? 'unknown'
            ]);
            
            // Store file
            $filePath = "drivers/{$driverFirebaseUid}/documents/" . time() . '_' . $file->getClientOriginalName();
            $storedPath = Storage::disk('public')->put($filePath, file_get_contents($file));
            
            if (!$storedPath) {
                Log::error('Failed to store document file');
                return null;
            }
            
            // Prepare document data
            $documentRecord = [
                'driver_firebase_uid' => $driverFirebaseUid,
                'document_type' => $documentData['document_type'],
                'document_category' => DriverDocument::getCategoryForType($documentData['document_type']),
                'document_name' => $documentData['document_name'] ?? $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_url' => Storage::disk('public')->url($filePath),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'document_number' => $documentData['document_number'] ?? null,
                'issue_date' => $documentData['issue_date'] ?? null,
                'expiry_date' => $documentData['expiry_date'] ?? null,
                'issuing_authority' => $documentData['issuing_authority'] ?? null,
                'issuing_country' => $documentData['issuing_country'] ?? null,
                'issuing_state' => $documentData['issuing_state'] ?? null,
                'verification_status' => DriverDocument::VERIFICATION_PENDING,
                'is_required' => DriverDocument::isDocumentTypeRequired($documentData['document_type']),
                'is_sensitive' => DriverDocument::isDocumentTypeSensitive($documentData['document_type']),
                'status' => DriverDocument::STATUS_ACTIVE,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];
            
            $result = $this->firestoreService->collection($this->collections['documents'])->create($documentRecord);
            
            if ($result) {
                // Create activity
                DriverActivity::createActivity($driverFirebaseUid, DriverActivity::TYPE_DOCUMENT_UPLOAD, [
                    'title' => 'Document Uploaded',
                    'description' => "Document uploaded: " . ($documentData['document_name'] ?? $file->getClientOriginalName()),
                    'metadata' => [
                        'document_id' => $result['id'] ?? null,
                        'document_type' => $documentData['document_type']
                    ]
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify document
     */
    public function verifyDocument(string $documentId, string $verifiedBy = null, string $notes = null): bool
    {
        try {
            Log::info('Verifying document', ['document_id' => $documentId]);
            
            $updateData = [
                'verification_status' => DriverDocument::VERIFICATION_VERIFIED,
                'verification_date' => now()->toDateTimeString(),
                'verified_by' => $verifiedBy,
                'verification_notes' => $notes,
                'updated_at' => now()->toDateTimeString()
            ];
            
            $result = $this->firestoreService->collection($this->collections['documents'])->update($documentId, $updateData);
            
            if ($result) {
                // Get document to create activity
                $document = $this->firestoreService->collection($this->collections['documents'])->find($documentId);
                if ($document && isset($document['driver_firebase_uid'])) {
                    DriverActivity::createActivity($document['driver_firebase_uid'], DriverActivity::TYPE_DOCUMENT_UPLOAD, [
                        'title' => 'Document Verified',
                        'description' => "Document has been verified: " . ($document['document_name'] ?? 'Unknown'),
                        'metadata' => [
                            'document_id' => $documentId,
                            'verified_by' => $verifiedBy
                        ]
                    ]);
                }
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error verifying document: ' . $e->getMessage());
            return false;
        }
    }

    // ============ LICENSE MANAGEMENT ============

    /**
     * Get driver licenses
     */
    public function getDriverLicenses(string $driverFirebaseUid): array
    {
        try {
            Log::info('Getting licenses for driver', ['firebase_uid' => $driverFirebaseUid]);
            
            $licenses = $this->firestoreService->collection($this->collections['licenses'])->getAll();
            
            // Filter by driver
            $driverLicenses = array_filter($licenses, function($license) use ($driverFirebaseUid) {
                return ($license['driver_firebase_uid'] ?? '') === $driverFirebaseUid;
            });
            
            return array_values($driverLicenses);

        } catch (\Exception $e) {
            Log::error('Error getting driver licenses: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Add driver license
     */
    public function addDriverLicense(array $licenseData): ?array
    {
        try {
            Log::info('Adding driver license', ['driver_uid' => $licenseData['driver_firebase_uid'] ?? 'unknown']);
            
            $licenseData['status'] = $licenseData['status'] ?? DriverLicense::STATUS_VALID;
            $licenseData['verification_status'] = $licenseData['verification_status'] ?? DriverLicense::VERIFICATION_PENDING;
            $licenseData['created_at'] = now()->toDateTimeString();
            $licenseData['updated_at'] = now()->toDateTimeString();
            
            $result = $this->firestoreService->collection($this->collections['licenses'])->create($licenseData);
            
            if ($result && isset($licenseData['driver_firebase_uid'])) {
                // Create activity
                DriverActivity::createActivity($licenseData['driver_firebase_uid'], DriverActivity::TYPE_PROFILE_UPDATE, [
                    'title' => 'License Added',
                    'description' => "Driver license added: " . ($licenseData['license_number'] ?? 'Unknown'),
                    'metadata' => ['license_id' => $result['id'] ?? null]
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error adding driver license: ' . $e->getMessage());
            return null;
        }
    }

    // ============ BULK OPERATIONS ============

    /**
     * Perform bulk action on drivers
     */
    public function performBulkAction(string $action, array $driverIds): array
    {
        try {
            Log::info('Performing bulk action on drivers', [
                'action' => $action, 
                'driver_count' => count($driverIds)
            ]);

            $processedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($driverIds as $driverId) {
                try {
                    $success = false;

                    switch ($action) {
                        case 'activate':
                            $success = $this->updateDriverStatus($driverId, Driver::STATUS_ACTIVE);
                            break;
                        case 'deactivate':
                            $success = $this->updateDriverStatus($driverId, Driver::STATUS_INACTIVE);
                            break;
                        case 'suspend':
                            $success = $this->updateDriverStatus($driverId, Driver::STATUS_SUSPENDED);
                            break;
                        case 'verify':
                            $success = $this->updateDriverVerificationStatus($driverId, Driver::VERIFICATION_VERIFIED);
                            break;
                        case 'delete':
                            $success = $this->deleteDriver($driverId);
                            break;
                    }

                    if ($success) {
                        $processedCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Failed to {$action} driver {$driverId}";
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Error with driver {$driverId}: " . $e->getMessage();
                    Log::warning('Bulk action failed for driver', [
                        'driver_id' => $driverId, 
                        'action' => $action, 
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Bulk action completed', [
                'action' => $action,
                'processed' => $processedCount,
                'failed' => $failedCount
            ]);

            return [
                'success' => true,
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Log::error('Bulk action exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
            ];
        }
    }

    // ============ STATISTICS AND ANALYTICS ============

    /**
     * Get driver statistics
     */
    public function getDriverStatistics(): array
    {
        try {
            $drivers = $this->getAllDrivers(['limit' => 1000]);
            
            $stats = [
                'total_drivers' => count($drivers),
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

            $oneWeekAgo = now()->subWeek();
            $totalRating = 0;
            $ratedDrivers = 0;

            foreach ($drivers as $driver) {
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

            // Calculate average rating
            if ($ratedDrivers > 0) {
                $stats['average_rating'] = round($totalRating / $ratedDrivers, 2);
            }

            Log::debug('Driver statistics calculated', $stats);
            return $stats;

        } catch (\Exception $e) {
            Log::error('Error getting driver statistics: ' . $e->getMessage());
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
    }

    // ============ HELPER METHODS ============

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
            'phone' => !empty($driver['phone']) ? trim($driver['phone']) : null,
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
     * Delete driver related data
     */
    private function deleteDriverRelatedData(string $driverFirebaseUid): void
    {
        try {
            // Delete vehicles
            $vehicles = $this->getDriverVehicles($driverFirebaseUid);
            foreach ($vehicles as $vehicle) {
                $this->firestoreService->collection($this->collections['vehicles'])->delete($vehicle['id']);
            }

            // Delete documents
            $documents = $this->getDriverDocuments($driverFirebaseUid);
            foreach ($documents as $document) {
                // Delete file if exists
                if (isset($document['file_path'])) {
                    Storage::disk('public')->delete($document['file_path']);
                }
                $this->firestoreService->collection($this->collections['documents'])->delete($document['id']);
            }

            // Delete activities (keep for audit trail - just mark as archived)
            $activities = $this->getDriverActivities($driverFirebaseUid);
            foreach ($activities as $activity) {
                $this->firestoreService->collection($this->collections['activities'])->update($activity['id'], [
                    'archived_at' => now()->toDateTimeString(),
                    'status' => 'archived'
                ]);
            }

            // Delete licenses
            $licenses = $this->getDriverLicenses($driverFirebaseUid);
            foreach ($licenses as $license) {
                $this->firestoreService->collection($this->collections['licenses'])->delete($license['id']);
            }

            Log::info('Driver related data deleted', ['firebase_uid' => $driverFirebaseUid]);

        } catch (\Exception $e) {
            Log::error('Error deleting driver related data: ' . $e->getMessage());
        }
    }

    /**
     * Sync drivers from Firebase Auth
     */
    public function syncFirebaseDrivers(): array
    {
        try {
            Log::info('Starting Firebase drivers sync');

            // Generate sample drivers for demonstration
            $sampleDrivers = $this->generateSampleDrivers();
            
            $syncedCount = 0;
            $failedCount = 0;

            foreach ($sampleDrivers as $driverData) {
                try {
                    // Check if driver already exists
                    $existingDriver = $this->getDriverById($driverData['firebase_uid']);

                    if ($existingDriver) {
                        // Update existing driver
                        $updateData = array_merge($driverData, ['updated_at' => now()->toDateTimeString()]);
                        unset($updateData['created_at']); // Don't overwrite creation date
                        
                        if ($this->updateDriver($driverData['firebase_uid'], $updateData)) {
                            $syncedCount++;
                        } else {
                            $failedCount++;
                        }
                    } else {
                        // Create new driver
                        if ($this->createDriver($driverData)) {
                            $syncedCount++;
                        } else {
                            $failedCount++;
                        }
                    }

                } catch (\Exception $e) {
                    Log::warning('Failed to sync driver', ['firebase_uid' => $driverData['firebase_uid'], 'error' => $e->getMessage()]);
                    $failedCount++;
                }
            }

            Log::info('Firebase drivers sync completed', [
                'synced' => $syncedCount,
                'failed' => $failedCount
            ]);

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'failed_count' => $failedCount,
                'message' => "Synced {$syncedCount} drivers successfully"
            ];

        } catch (\Exception $e) {
            Log::error('Firebase drivers sync failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate sample drivers for sync demonstration
     */
    private function generateSampleDrivers(): array
    {
        return [
            [
                'firebase_uid' => 'driver_001_' . time(),
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1234567890',
                'photo_url' => 'https://ui-avatars.com/api/?name=John+Smith&color=003366&background=FFA500',
                'date_of_birth' => '1985-05-15',
                'gender' => 'male',
                'address' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
                'license_number' => 'NY123456789',
                'license_expiry' => now()->addYears(2)->format('Y-m-d'),
                'status' => Driver::STATUS_ACTIVE,
                'verification_status' => Driver::VERIFICATION_VERIFIED,
                'availability_status' => Driver::AVAILABILITY_AVAILABLE,
                'rating' => 4.8,
                'total_rides' => 150,
                'completed_rides' => 145,
                'cancelled_rides' => 5,
                'total_earnings' => 2500.00,
                'join_date' => now()->subDays(30)->toDateTimeString(),
            ],
            [
                'firebase_uid' => 'driver_002_' . time(),
                'name' => 'Maria Rodriguez',
                'email' => 'maria.rodriguez@example.com',
                'phone' => '+1234567891',
                'photo_url' => 'https://ui-avatars.com/api/?name=Maria+Rodriguez&color=003366&background=FFA500',
                'date_of_birth' => '1990-03-22',
                'gender' => 'female',
                'address' => '456 Oak Ave',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'postal_code' => '90001',
                'country' => 'US',
                'license_number' => 'CA987654321',
                'license_expiry' => now()->addYears(3)->format('Y-m-d'),
                'status' => Driver::STATUS_ACTIVE,
                'verification_status' => Driver::VERIFICATION_VERIFIED,
                'availability_status' => Driver::AVAILABILITY_BUSY,
                'rating' => 4.9,
                'total_rides' => 220,
                'completed_rides' => 215,
                'cancelled_rides' => 5,
                'total_earnings' => 3200.00,
                'join_date' => now()->subDays(45)->toDateTimeString(),
            ],
            [
                'firebase_uid' => 'driver_003_' . time(),
                'name' => 'Ahmed Hassan',
                'email' => 'ahmed.hassan@example.com',
                'phone' => '+1234567892',
                'photo_url' => 'https://ui-avatars.com/api/?name=Ahmed+Hassan&color=003366&background=FFA500',
                'date_of_birth' => '1988-08-10',
                'gender' => 'male',
                'address' => '789 Pine Rd',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60601',
                'country' => 'US',
                'license_number' => 'IL456789123',
                'license_expiry' => now()->addYears(1)->format('Y-m-d'),
                'status' => Driver::STATUS_PENDING,
                'verification_status' => Driver::VERIFICATION_PENDING,
                'availability_status' => Driver::AVAILABILITY_OFFLINE,
                'rating' => 0.0,
                'total_rides' => 0,
                'completed_rides' => 0,
                'cancelled_rides' => 0,
                'total_earnings' => 0.00,
                'join_date' => now()->subDays(3)->toDateTimeString(),
            ],
            [
                'firebase_uid' => 'driver_004_' . time(),
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
                'phone' => '+1234567893',
                'photo_url' => 'https://ui-avatars.com/api/?name=Sarah+Johnson&color=003366&background=FFA500',
                'date_of_birth' => '1992-12-05',
                'gender' => 'female',
                'address' => '321 Elm St',
                'city' => 'Miami',
                'state' => 'FL',
                'postal_code' => '33101',
                'country' => 'US',
                'license_number' => 'FL789123456',
                'license_expiry' => now()->addMonths(6)->format('Y-m-d'),
                'status' => Driver::STATUS_ACTIVE,
                'verification_status' => Driver::VERIFICATION_VERIFIED,
                'availability_status' => Driver::AVAILABILITY_AVAILABLE,
                'rating' => 4.7,
                'total_rides' => 89,
                'completed_rides' => 85,
                'cancelled_rides' => 4,
                'total_earnings' => 1650.00,
                'join_date' => now()->subDays(15)->toDateTimeString(),
            ],
            [
                'firebase_uid' => 'driver_005_' . time(),
                'name' => 'Robert Brown',
                'email' => 'robert.brown@example.com',
                'phone' => '+1234567894',
                'photo_url' => 'https://ui-avatars.com/api/?name=Robert+Brown&color=003366&background=FFA500',
                'date_of_birth' => '1975-07-18',
                'gender' => 'male',
                'address' => '654 Maple Dr',
                'city' => 'Phoenix',
                'state' => 'AZ',
                'postal_code' => '85001',
                'country' => 'US',
                'license_number' => 'AZ321654987',
                'license_expiry' => now()->addYears(4)->format('Y-m-d'),
                'status' => Driver::STATUS_SUSPENDED,
                'verification_status' => Driver::VERIFICATION_VERIFIED,
                'availability_status' => Driver::AVAILABILITY_OFFLINE,
                'rating' => 4.2,
                'total_rides' => 320,
                'completed_rides' => 300,
                'cancelled_rides' => 20,
                'total_earnings' => 4800.00,
                'join_date' => now()->subDays(90)->toDateTimeString(),
            ]
        ];
    }

    /**
     * Update driver location
     */
    public function updateDriverLocation(string $driverFirebaseUid, float $latitude, float $longitude, string $address = null): bool
    {
        try {
            Log::info('Updating driver location', [
                'firebase_uid' => $driverFirebaseUid,
                'lat' => $latitude,
                'lng' => $longitude
            ]);

            $updateData = [
                'current_location_lat' => $latitude,
                'current_location_lng' => $longitude,
                'last_location_update' => now()->toDateTimeString(),
                'last_active' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];

            if ($address) {
                $updateData['current_address'] = $address;
            }

            $result = $this->firestoreService->collection($this->collections['drivers'])->update($driverFirebaseUid, $updateData);

            if ($result) {
                // Create location update activity (only for significant moves)
                // You might want to implement distance checking to avoid too many activities
                DriverActivity::createActivity($driverFirebaseUid, DriverActivity::TYPE_LOCATION_UPDATE, [
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
    public function updateDriverAvailability(string $driverFirebaseUid, string $availabilityStatus): bool
    {
        try {
            Log::info('Updating driver availability', [
                'firebase_uid' => $driverFirebaseUid,
                'availability_status' => $availabilityStatus
            ]);

            $updateData = [
                'availability_status' => $availabilityStatus,
                'last_active' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];

            $result = $this->firestoreService->collection($this->collections['drivers'])->update($driverFirebaseUid, $updateData);

            if ($result) {
                DriverActivity::createActivity($driverFirebaseUid, DriverActivity::TYPE_STATUS_CHANGE, [
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
     * Get total drivers count
     */
    public function getTotalDriversCount(): int
    {
        try {
            return $this->firestoreService->collection($this->collections['drivers'])->count();
        } catch (\Exception $e) {
            Log::error('Error getting total drivers count: ' . $e->getMessage());
            return 0;
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
        try {
            Log::info('Getting drivers by status', ['status' => $status]);
            
            return $this->getAllDrivers([
                'status' => $status,
                'limit' => 1000
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting drivers by status: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get drivers by verification status
     */
    public function getDriversByVerificationStatus(string $verificationStatus): array
    {
        try {
            Log::info('Getting drivers by verification status', ['verification_status' => $verificationStatus]);
            
            return $this->getAllDrivers([
                'verification_status' => $verificationStatus,
                'limit' => 1000
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting drivers by verification status: ' . $e->getMessage());
            return [];
        }
    }
}
