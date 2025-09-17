<?php

namespace App\Modules\Driver\Models;

use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Ride
{
    protected $firestoreService;
    protected $collection = 'rides';

    // Ride status constants
    const STATUS_PENDING = 'pending';
    const STATUS_REQUESTED = 'requested';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DRIVER_ARRIVED = 'driver_arrived';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Ride type constants
    const TYPE_STANDARD = 'standard';
    const TYPE_PREMIUM = 'premium';
    const TYPE_SHARED = 'shared';
    const TYPE_XL = 'xl';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_SCHEDULED = 'scheduled';

    // Payment status constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_COMPLETED = 'completed';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    // Cancellation reasons
    const CANCEL_DRIVER_NO_SHOW = 'driver_no_show';
    const CANCEL_PASSENGER_NO_SHOW = 'passenger_no_show';
    const CANCEL_DRIVER_REQUEST = 'driver_request';
    const CANCEL_PASSENGER_REQUEST = 'passenger_request';
    const CANCEL_SYSTEM = 'system';
    const CANCEL_EMERGENCY = 'emergency';

    public function __construct()
    {
        try {
            $this->firestoreService = new FirestoreService();
            Log::debug('Ride model initialized with FirestoreService');
        } catch (\Exception $e) {
            Log::error('Ride model initialization failed: ' . $e->getMessage());
            throw new \Exception('Could not initialize Firestore service: ' . $e->getMessage());
        }
    }

    /**
     * Get all rides with optional filters
     */
    public function getAllRides(array $filters = []): array
    {
        try {
            Log::info('Ride model: Getting all rides', ['filters' => $filters]);

            $limit = $filters['limit'] ?? 100;
            $useCache = !isset($filters['no_cache']) || !$filters['no_cache'];

            $rides = $this->firestoreService
                ->collection($this->collection)
                ->getAll($limit, $useCache);

            // Apply client-side filters
            if (!empty($filters['status'])) {
                $rides = array_filter($rides, function ($ride) use ($filters) {
                    return ($ride['status'] ?? '') === $filters['status'];
                });
            }

            if (!empty($filters['ride_type'])) {
                $rides = array_filter($rides, function ($ride) use ($filters) {
                    return ($ride['ride_type'] ?? '') === $filters['ride_type'];
                });
            }

            if (!empty($filters['payment_status'])) {
                $rides = array_filter($rides, function ($ride) use ($filters) {
                    return ($ride['payment_status'] ?? '') === $filters['payment_status'];
                });
            }

            if (!empty($filters['driver_firebase_uid'])) {
                $rides = array_filter($rides, function ($ride) use ($filters) {
                    return ($ride['driver_firebase_uid'] ?? '') === $filters['driver_firebase_uid'];
                });
            }

            // Apply date filters
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $rides = array_filter($rides, function ($ride) use ($filters) {
                    $rideDate = Carbon::parse($ride['created_at'] ?? now());

                    if (!empty($filters['date_from'])) {
                        $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
                        if ($rideDate->lt($dateFrom)) {
                            return false;
                        }
                    }

                    if (!empty($filters['date_to'])) {
                        $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
                        if ($rideDate->gt($dateTo)) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            // Sort by created_at descending
            usort($rides, function ($a, $b) {
                $dateA = Carbon::parse($a['created_at'] ?? now());
                $dateB = Carbon::parse($b['created_at'] ?? now());
                return $dateB->timestamp - $dateA->timestamp;
            });

            // Apply limit after filtering
            if (isset($filters['limit']) && $filters['limit'] > 0) {
                $rides = array_slice($rides, 0, $filters['limit']);
            }

            // Format all rides
            $rides = array_map([$this, 'formatRideData'], $rides);

            Log::info('Retrieved rides successfully', [
                'count' => count($rides),
                'filters' => $filters
            ]);

            return array_values($rides); // Reindex array

        } catch (\Exception $e) {
            Log::error('Error retrieving rides: ' . $e->getMessage(), [
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
            Log::info('Getting ride by ID', ['ride_id' => $rideId]);

            $ride = $this->firestoreService
                ->collection($this->collection)
                ->find($rideId);

            if ($ride) {
                $ride = $this->formatRideData($ride);
                Log::info('Ride found', ['ride_id' => $rideId]);
                return $ride;
            }

            Log::warning('Ride not found', ['ride_id' => $rideId]);
            return null;
        } catch (\Exception $e) {
            Log::error('Error getting ride by ID: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get rides by driver
     */
    public function getRidesByDriver(string $driverFirebaseUid, array $filters = []): array
    {
        try {
            Log::info('Getting rides by driver', [
                'driver_uid' => $driverFirebaseUid,
                'filters' => $filters
            ]);

            // Add driver filter
            $filters['driver_firebase_uid'] = $driverFirebaseUid;

            return $this->getAllRides($filters);
        } catch (\Exception $e) {
            Log::error('Error getting rides by driver: ' . $e->getMessage(), [
                'driver_uid' => $driverFirebaseUid,
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Search rides by multiple criteria
     */
    public function searchRides(string $searchTerm, array $filters = []): array
    {
        try {
            Log::info('Searching rides', [
                'search_term' => $searchTerm,
                'filters' => $filters
            ]);

            // Get all rides first
            $allRides = $this->getAllRides($filters);

            if (empty($searchTerm)) {
                return $allRides;
            }

            $searchTerm = strtolower($searchTerm);
            $matchingRides = [];

            foreach ($allRides as $ride) {
                if ($this->matchesSearchTerm($ride, $searchTerm)) {
                    $matchingRides[] = $ride;
                }
            }

            Log::info('Search completed', [
                'search_term' => $searchTerm,
                'total_rides' => count($allRides),
                'matching_rides' => count($matchingRides)
            ]);

            return $matchingRides;
        } catch (\Exception $e) {
            Log::error('Error searching rides: ' . $e->getMessage(), [
                'search_term' => $searchTerm,
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Create new ride
     */
    public function createRide(array $data): ?array
    {
        try {
            Log::info('Creating new ride', [
                'driver_uid' => $data['driver_firebase_uid'] ?? 'unknown'
            ]);

            $data = $this->prepareRideData($data);

            $result = $this->firestoreService
                ->collection($this->collection)
                ->create($data);

            if ($result) {
                $result = $this->formatRideData($result);
                Log::info('Ride created successfully', [
                    'ride_id' => $result['id'],
                    'driver_uid' => $data['driver_firebase_uid'] ?? 'unknown'
                ]);
                return $result;
            }

            Log::error('Failed to create ride');
            return null;
        } catch (\Exception $e) {
            Log::error('Error creating ride: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Update ride
     */
    public function updateRide(string $rideId, array $data): bool
    {
        try {
            Log::info('Updating ride', [
                'ride_id' => $rideId,
                'fields' => array_keys($data)
            ]);

            $data['updated_at'] = now()->format('Y-m-d H:i:s');

            // Remove empty values
            $data = array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            });

            $result = $this->firestoreService
                ->collection($this->collection)
                ->update($rideId, $data);

            if ($result) {
                Log::info('Ride updated successfully', [
                    'ride_id' => $rideId,
                    'updated_fields' => array_keys($data)
                ]);
                return true;
            }

            Log::error('Failed to update ride', ['ride_id' => $rideId]);
            return false;
        } catch (\Exception $e) {
            Log::error('Error updating ride: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Delete ride
     */
    public function deleteRide(string $rideId): bool
    {
        try {
            Log::info('Deleting ride', ['ride_id' => $rideId]);

            $result = $this->firestoreService
                ->collection($this->collection)
                ->delete($rideId);

            if ($result) {
                Log::info('Ride deleted successfully', ['ride_id' => $rideId]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error deleting ride: ' . $e->getMessage(), [
                'ride_id' => $rideId
            ]);
            return false;
        }
    }

    /**
     * Get rides by status
     */
    public function getRidesByStatus(string $status, array $filters = []): array
    {
        $filters['status'] = $status;
        return $this->getAllRides($filters);
    }

    /**
     * Get active rides for driver
     */
    public function getActiveRidesForDriver(string $driverFirebaseUid): array
    {
        try {
            $allRides = $this->getRidesByDriver($driverFirebaseUid, ['limit' => 1000]);

            $activeStatuses = [
                self::STATUS_PENDING,
                self::STATUS_REQUESTED,
                self::STATUS_ACCEPTED,
                self::STATUS_DRIVER_ARRIVED,
                self::STATUS_IN_PROGRESS
            ];

            $activeRides = array_filter($allRides, function ($ride) use ($activeStatuses) {
                return in_array($ride['status'] ?? '', $activeStatuses);
            });

            return array_values($activeRides);
        } catch (\Exception $e) {
            Log::error('Error getting active rides for driver: ' . $e->getMessage(), [
                'driver_uid' => $driverFirebaseUid
            ]);
            return [];
        }
    }

    /**
     * Get ride statistics
     */
    public function getRideStatistics(array $filters = []): array
    {
        try {
            $rides = $this->getAllRides(array_merge($filters, ['limit' => 10000, 'no_cache' => true]));

            $stats = [
                'total_rides' => count($rides),
                'completed_rides' => 0,
                'cancelled_rides' => 0,
                'in_progress_rides' => 0,
                'pending_rides' => 0,
                'total_earnings' => 0,
                'average_rating' => 0,
                'total_distance' => 0,
                'total_duration' => 0,
            ];

            if (empty($rides)) {
                return $stats;
            }

            $totalRating = 0;
            $ratedRides = 0;

            foreach ($rides as $ride) {
                // Count by status
                switch ($ride['status']) {
                    case self::STATUS_COMPLETED:
                        $stats['completed_rides']++;
                        break;
                    case self::STATUS_CANCELLED:
                        $stats['cancelled_rides']++;
                        break;
                    case self::STATUS_IN_PROGRESS:
                    case self::STATUS_ACCEPTED:
                    case self::STATUS_DRIVER_ARRIVED:
                        $stats['in_progress_rides']++;
                        break;
                    default:
                        $stats['pending_rides']++;
                        break;
                }

                // Sum earnings
                if (isset($ride['actual_fare'])) {
                    $stats['total_earnings'] += (float) $ride['actual_fare'];
                } elseif (isset($ride['estimated_fare'])) {
                    $stats['total_earnings'] += (float) $ride['estimated_fare'];
                }

                // Sum distance
                if (isset($ride['distance_km'])) {
                    $stats['total_distance'] += (float) $ride['distance_km'];
                }

                // Sum duration
                if (isset($ride['duration_minutes'])) {
                    $stats['total_duration'] += (int) $ride['duration_minutes'];
                }

                // Calculate average rating
                if (isset($ride['driver_rating']) && $ride['driver_rating'] > 0) {
                    $totalRating += (float) $ride['driver_rating'];
                    $ratedRides++;
                }
            }

            // Calculate averages
            if ($ratedRides > 0) {
                $stats['average_rating'] = round($totalRating / $ratedRides, 2);
            }

            $stats['completion_rate'] = $stats['total_rides'] > 0
                ? round(($stats['completed_rides'] / $stats['total_rides']) * 100, 2)
                : 0;

            $stats['cancellation_rate'] = $stats['total_rides'] > 0
                ? round(($stats['cancelled_rides'] / $stats['total_rides']) * 100, 2)
                : 0;

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error calculating ride statistics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format ride data for consistent output
     */
    private function formatRideData(array $data): array
    {
        // Ensure required fields exist with default values
        $defaults = [
            'ride_id' => $data['id'] ?? uniqid('ride_'),
            'driver_firebase_uid' => '',
            'passenger_firebase_uid' => '',
            'passenger_name' => 'Unknown Passenger',
            'pickup_address' => 'Unknown',
            'dropoff_address' => 'Unknown',
            'status' => self::STATUS_PENDING,
            'ride_type' => self::TYPE_STANDARD,
            'payment_status' => self::PAYMENT_PENDING,
            'estimated_fare' => 0.00,
            'actual_fare' => 0.00,
            'distance_km' => 0.00,
            'duration_minutes' => 0,
            'driver_rating' => 0,
            'passenger_rating' => 0,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];

        return array_merge($defaults, $data);
    }

    /**
     * Prepare ride data before saving
     */
    private function prepareRideData(array $data): array
    {
        // Set default values
        $defaults = [
            'ride_id' => 'RIDE_' . strtoupper(uniqid()),
            'status' => self::STATUS_PENDING,
            'ride_type' => self::TYPE_STANDARD,
            'payment_status' => self::PAYMENT_PENDING,
            'estimated_fare' => 0.00,
            'actual_fare' => 0.00,
            'distance_km' => 0.00,
            'duration_minutes' => 0,
            'driver_rating' => 0,
            'passenger_rating' => 0,
            'requested_at' => now()->format('Y-m-d H:i:s'),
        ];

        return array_merge($defaults, $data);
    }

    /**
     * Check if ride data matches search term
     */
    private function matchesSearchTerm(array $ride, string $searchTerm): bool
    {
        $searchFields = [
            'ride_id',
            'passenger_name',
            'pickup_address',
            'dropoff_address',
            'status',
            'ride_type',
            'driver_name', // May be added by service layer
        ];

        foreach ($searchFields as $field) {
            if (isset($ride[$field]) && stripos($ride[$field], $searchTerm) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available ride types
     */
    public static function getRideTypes(): array
    {
        return [
            self::TYPE_STANDARD => 'Standard',
            self::TYPE_PREMIUM => 'Premium',
            self::TYPE_SHARED => 'Shared',
            self::TYPE_XL => 'XL',
            self::TYPE_DELIVERY => 'Delivery',
            self::TYPE_SCHEDULED => 'Scheduled'
        ];
    }

    /**
     * Get available ride statuses
     */
    public static function getRideStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DRIVER_ARRIVED => 'Driver Arrived',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }

    /**
     * Get payment statuses
     */
    public static function getPaymentStatuses(): array
    {
        return [
            self::PAYMENT_PENDING => 'Pending',
            self::PAYMENT_COMPLETED => 'Completed',
            self::PAYMENT_FAILED => 'Failed',
            self::PAYMENT_REFUNDED => 'Refunded'
        ];
    }

    /**
     * Get cancellation reasons
     */
    public static function getCancellationReasons(): array
    {
        return [
            self::CANCEL_DRIVER_NO_SHOW => 'Driver No Show',
            self::CANCEL_PASSENGER_NO_SHOW => 'Passenger No Show',
            self::CANCEL_DRIVER_REQUEST => 'Driver Requested',
            self::CANCEL_PASSENGER_REQUEST => 'Passenger Requested',
            self::CANCEL_SYSTEM => 'System Cancelled',
            self::CANCEL_EMERGENCY => 'Emergency'
        ];
    }

    /**
     * Test connection to Firestore
     */
    public function testConnection(): array
    {
        try {
            return $this->firestoreService->healthCheck();
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get collection stats
     */
    public function getCollectionStats(): array
    {
        try {
            return $this->firestoreService->collection($this->collection)->getStats();
        } catch (\Exception $e) {
            Log::error('Error getting collection stats: ' . $e->getMessage());
            return [
                'collection' => $this->collection,
                'document_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}
