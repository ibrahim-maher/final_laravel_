<?php

namespace App\Modules\Driver\Services;

use App\Modules\Driver\Models\Ride;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DriverRideService
{
    protected $firestoreService;
    protected $rideModel;

    public function __construct()
    {
        $this->firestoreService = new FirestoreService();
        $this->rideModel = new Ride();
    }

    /**
     * Get all rides with optional filters
     */
    public function getAllRides(array $filters = []): array
    {
        try {
            Log::info('DriverRideService: Getting all rides', ['filters' => $filters]);

            $limit = $filters['limit'] ?? 100;
            $useCache = !isset($filters['no_cache']) || !$filters['no_cache'];

            $rides = $this->firestoreService
                ->collection('rides')
                ->getAll($limit, $useCache);

            // Apply client-side filters
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $rides = array_filter($rides, function ($ride) use ($search) {
                    return stripos($ride['ride_id'] ?? '', $search) !== false ||
                        stripos($ride['passenger_name'] ?? '', $search) !== false ||
                        stripos($ride['driver_name'] ?? '', $search) !== false ||
                        stripos($ride['pickup_address'] ?? '', $search) !== false ||
                        stripos($ride['dropoff_address'] ?? '', $search) !== false;
                });
            }

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

            return array_values($rides);
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
                ->collection('rides')
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
     * Create new ride
     */
    public function createRide(array $rideData): ?array
    {
        try {
            Log::info('Creating new ride', [
                'driver_uid' => $rideData['driver_firebase_uid'] ?? 'unknown'
            ]);

            $rideData = $this->prepareRideData($rideData);

            $result = $this->firestoreService
                ->collection('rides')
                ->create($rideData);

            if ($result) {
                $result = $this->formatRideData($result);
                Log::info('Ride created successfully', [
                    'ride_id' => $result['id'],
                    'driver_uid' => $rideData['driver_firebase_uid'] ?? 'unknown'
                ]);
                return $result;
            }

            Log::error('Failed to create ride');
            return null;
        } catch (\Exception $e) {
            Log::error('Error creating ride: ' . $e->getMessage(), [
                'data' => $rideData,
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
                ->collection('rides')
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
     * Update ride status
     */
    public function updateRideStatus(string $rideId, string $status, array $additionalData = []): bool
    {
        try {
            $updateData = array_merge($additionalData, [
                'status' => $status,
                'status_updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            // Set timestamps based on status
            switch ($status) {
                case Ride::STATUS_ACCEPTED:
                    $updateData['accepted_at'] = now()->format('Y-m-d H:i:s');
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

            return $this->updateRide($rideId, $updateData);
        } catch (\Exception $e) {
            Log::error('Error updating ride status: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'status' => $status
            ]);
            return false;
        }
    }

    /**
     * Complete ride
     */
    public function completeRide(string $rideId, array $completionData = []): bool
    {
        try {
            $updateData = array_merge($completionData, [
                'status' => Ride::STATUS_COMPLETED,
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'status_updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            // If actual fare is not provided but we have estimated fare, use it
            $ride = $this->getRideById($rideId);
            if (!isset($updateData['actual_fare']) && isset($ride['estimated_fare'])) {
                $updateData['actual_fare'] = $ride['estimated_fare'];
            }

            return $this->updateRide($rideId, $updateData);
        } catch (\Exception $e) {
            Log::error('Error completing ride: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'completion_data' => $completionData
            ]);
            return false;
        }
    }

    /**
     * Cancel ride
     */
    public function cancelRide(string $rideId, string $reason = null, string $cancelledBy = 'system', array $additionalData = []): bool
    {
        try {
            $updateData = array_merge($additionalData, [
                'status' => Ride::STATUS_CANCELLED,
                'cancelled_at' => now()->format('Y-m-d H:i:s'),
                'cancelled_by' => $cancelledBy,
                'status_updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            if ($reason) {
                $updateData['cancellation_reason'] = $reason;
            }

            return $this->updateRide($rideId, $updateData);
        } catch (\Exception $e) {
            Log::error('Error cancelling ride: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'reason' => $reason
            ]);
            return false;
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
     * Get rides by status
     */
    public function getRidesByStatus(string $status, array $filters = []): array
    {
        $filters['status'] = $status;
        return $this->getAllRides($filters);
    }

    /**
     * Search rides
     */
    public function searchRides(string $searchTerm, array $filters = []): array
    {
        try {
            Log::info('Searching rides', [
                'search_term' => $searchTerm,
                'filters' => $filters
            ]);

            $filters['search'] = $searchTerm;
            return $this->getAllRides($filters);
        } catch (\Exception $e) {
            Log::error('Error searching rides: ' . $e->getMessage(), [
                'search_term' => $searchTerm,
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
            $allRides = $this->getRidesByDriver($driverFirebaseUid, ['limit' => 1000]);

            $activeStatuses = [
                Ride::STATUS_PENDING,
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ACCEPTED,
                Ride::STATUS_DRIVER_ARRIVED,
                Ride::STATUS_IN_PROGRESS
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
                'completion_rate' => 0,
                'cancellation_rate' => 0
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
            return $this->getDefaultStatistics();
        }
    }

    /**
     * Get ride summary data
     */
    public function getRideSummaryData(): array
    {
        try {
            $today = now()->startOfDay()->format('Y-m-d H:i:s');
            $weekStart = now()->startOfWeek()->format('Y-m-d H:i:s');
            $monthStart = now()->startOfMonth()->format('Y-m-d H:i:s');

            return [
                'today' => $this->getRideStatistics(['date_from' => $today]),
                'this_week' => $this->getRideStatistics(['date_from' => $weekStart]),
                'this_month' => $this->getRideStatistics(['date_from' => $monthStart])
            ];
        } catch (\Exception $e) {
            Log::error('Error getting ride summary data: ' . $e->getMessage());
            return [
                'today' => $this->getDefaultStatistics(),
                'this_week' => $this->getDefaultStatistics(),
                'this_month' => $this->getDefaultStatistics()
            ];
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
                ->collection('rides')
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
     * Get driver rides count
     */
    public function getDriverRidesCount(string $driverFirebaseUid): int
    {
        try {
            $rides = $this->getRidesByDriver($driverFirebaseUid, ['limit' => 10000]);
            return count($rides);
        } catch (\Exception $e) {
            Log::error('Error getting driver rides count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get driver earnings
     */
    public function getDriverEarnings(string $driverFirebaseUid, array $filters = []): float
    {
        try {
            $filters['status'] = Ride::STATUS_COMPLETED;
            $rides = $this->getRidesByDriver($driverFirebaseUid, $filters);

            $totalEarnings = 0;
            foreach ($rides as $ride) {
                if (isset($ride['actual_fare'])) {
                    $totalEarnings += (float) $ride['actual_fare'];
                } elseif (isset($ride['estimated_fare'])) {
                    $totalEarnings += (float) $ride['estimated_fare'];
                }
            }

            return $totalEarnings;
        } catch (\Exception $e) {
            Log::error('Error getting driver earnings: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get driver average rating
     */
    public function getDriverAverageRating(string $driverFirebaseUid): float
    {
        try {
            $rides = $this->getRidesByDriver($driverFirebaseUid, ['limit' => 1000]);

            $totalRating = 0;
            $ratedRides = 0;

            foreach ($rides as $ride) {
                if (isset($ride['driver_rating']) && $ride['driver_rating'] > 0) {
                    $totalRating += (float) $ride['driver_rating'];
                    $ratedRides++;
                }
            }

            return $ratedRides > 0 ? round($totalRating / $ratedRides, 2) : 0;
        } catch (\Exception $e) {
            Log::error('Error getting driver average rating: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Bulk update rides
     */
    public function bulkUpdateRides(array $rideIds, array $updateData): array
    {
        $updated = 0;
        $failed = 0;

        foreach ($rideIds as $rideId) {
            try {
                if ($this->updateRide($rideId, $updateData)) {
                    $updated++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("Bulk update failed for ride {$rideId}: " . $e->getMessage());
                $failed++;
            }
        }

        return [
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($rideIds)
        ];
    }

    /**
     * Export rides
     */
    public function exportRides(array $filters = []): array
    {
        try {
            $rides = $this->getAllRides(array_merge($filters, ['limit' => 10000]));

            return array_map(function ($ride) {
                return [
                    'ride_id' => $ride['ride_id'] ?? $ride['id'] ?? '',
                    'driver_firebase_uid' => $ride['driver_firebase_uid'] ?? '',
                    'passenger_name' => $ride['passenger_name'] ?? '',
                    'pickup_address' => $ride['pickup_address'] ?? '',
                    'dropoff_address' => $ride['dropoff_address'] ?? '',
                    'status' => $ride['status'] ?? '',
                    'ride_type' => $ride['ride_type'] ?? '',
                    'estimated_fare' => $ride['estimated_fare'] ?? 0,
                    'actual_fare' => $ride['actual_fare'] ?? 0,
                    'distance_km' => $ride['distance_km'] ?? 0,
                    'duration_minutes' => $ride['duration_minutes'] ?? 0,
                    'driver_rating' => $ride['driver_rating'] ?? 0,
                    'passenger_rating' => $ride['passenger_rating'] ?? 0,
                    'payment_status' => $ride['payment_status'] ?? '',
                    'created_at' => $ride['created_at'] ?? '',
                    'completed_at' => $ride['completed_at'] ?? '',
                    'cancelled_at' => $ride['cancelled_at'] ?? '',
                    'cancellation_reason' => $ride['cancellation_reason'] ?? ''
                ];
            }, $rides);
        } catch (\Exception $e) {
            Log::error('Error exporting rides: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get ride timeline/activities
     */
    public function getRideActivities(string $rideId): array
    {
        try {
            $ride = $this->getRideById($rideId);
            if (!$ride) {
                return [];
            }

            $activities = [];

            // Create timeline based on ride data
            if (!empty($ride['created_at'])) {
                $activities[] = [
                    'type' => 'ride_requested',
                    'title' => 'Ride Requested',
                    'description' => 'Ride was requested by passenger',
                    'timestamp' => $ride['created_at'],
                    'status' => 'completed'
                ];
            }

            if (!empty($ride['accepted_at'])) {
                $activities[] = [
                    'type' => 'ride_accepted',
                    'title' => 'Ride Accepted',
                    'description' => 'Driver accepted the ride request',
                    'timestamp' => $ride['accepted_at'],
                    'status' => 'completed'
                ];
            }

            if (!empty($ride['started_at'])) {
                $activities[] = [
                    'type' => 'ride_started',
                    'title' => 'Ride Started',
                    'description' => 'Driver started the trip',
                    'timestamp' => $ride['started_at'],
                    'status' => 'completed'
                ];
            }

            if (!empty($ride['completed_at'])) {
                $activities[] = [
                    'type' => 'ride_completed',
                    'title' => 'Ride Completed',
                    'description' => 'Trip was completed successfully',
                    'timestamp' => $ride['completed_at'],
                    'status' => 'completed'
                ];
            }

            if (!empty($ride['cancelled_at'])) {
                $activities[] = [
                    'type' => 'ride_cancelled',
                    'title' => 'Ride Cancelled',
                    'description' => 'Ride was cancelled: ' . ($ride['cancellation_reason'] ?? 'No reason provided'),
                    'timestamp' => $ride['cancelled_at'],
                    'status' => 'cancelled'
                ];
            }

            // Sort by timestamp
            usort($activities, function ($a, $b) {
                return strtotime($a['timestamp']) - strtotime($b['timestamp']);
            });

            return $activities;
        } catch (\Exception $e) {
            Log::error('Error getting ride activities: ' . $e->getMessage());
            return [];
        }
    }

    // ===================== PRIVATE HELPER METHODS =====================

    /**
     * Format ride data for consistent output
     */
    private function formatRideData(array $data): array
    {
        // Ensure required fields exist with default values
        $defaults = [
            'id' => $data['id'] ?? uniqid('ride_'),
            'ride_id' => $data['ride_id'] ?? $data['id'] ?? uniqid('ride_'),
            'driver_firebase_uid' => '',
            'passenger_firebase_uid' => '',
            'passenger_name' => 'Unknown Passenger',
            'driver_name' => 'Unknown Driver',
            'pickup_address' => 'Unknown',
            'dropoff_address' => 'Unknown',
            'pickup_latitude' => 0.0,
            'pickup_longitude' => 0.0,
            'dropoff_latitude' => 0.0,
            'dropoff_longitude' => 0.0,
            'status' => Ride::STATUS_PENDING,
            'ride_type' => Ride::TYPE_STANDARD,
            'payment_status' => Ride::PAYMENT_PENDING,
            'payment_method' => '',
            'estimated_fare' => 0.00,
            'actual_fare' => 0.00,
            'distance_km' => 0.00,
            'duration_minutes' => 0,
            'driver_rating' => 0,
            'passenger_rating' => 0,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
            'accepted_at' => '',
            'started_at' => '',
            'completed_at' => '',
            'cancelled_at' => '',
            'cancellation_reason' => '',
            'cancelled_by' => '',
            'notes' => '',
            'special_requests' => '',
            'passenger_phone' => ''
        ];

        $formatted = array_merge($defaults, $data);

        // Ensure numeric fields are properly typed
        $formatted['estimated_fare'] = (float) $formatted['estimated_fare'];
        $formatted['actual_fare'] = (float) $formatted['actual_fare'];
        $formatted['distance_km'] = (float) $formatted['distance_km'];
        $formatted['duration_minutes'] = (int) $formatted['duration_minutes'];
        $formatted['driver_rating'] = (float) $formatted['driver_rating'];
        $formatted['passenger_rating'] = (float) $formatted['passenger_rating'];
        $formatted['pickup_latitude'] = (float) $formatted['pickup_latitude'];
        $formatted['pickup_longitude'] = (float) $formatted['pickup_longitude'];
        $formatted['dropoff_latitude'] = (float) $formatted['dropoff_latitude'];
        $formatted['dropoff_longitude'] = (float) $formatted['dropoff_longitude'];

        return $formatted;
    }

    /**
     * Prepare ride data before saving
     */
    private function prepareRideData(array $data): array
    {
        // Set default values
        $defaults = [
            'ride_id' => 'RIDE_' . strtoupper(uniqid()),
            'status' => Ride::STATUS_PENDING,
            'ride_type' => Ride::TYPE_STANDARD,
            'payment_status' => Ride::PAYMENT_PENDING,
            'estimated_fare' => 0.00,
            'actual_fare' => 0.00,
            'distance_km' => 0.00,
            'duration_minutes' => 0,
            'driver_rating' => 0,
            'passenger_rating' => 0,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
            'requested_at' => now()->format('Y-m-d H:i:s'),
        ];

        return array_merge($defaults, $data);
    }

    /**
     * Get default statistics
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
            'cancellation_rate' => 0
        ];
    }

    /**
     * Clear ride cache
     */
    public function clearRideCache(): void
    {
        Cache::forget('ride_statistics');
        Cache::forget('ride_summary_data');
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
}
