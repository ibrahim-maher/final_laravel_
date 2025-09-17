<?php

namespace App\Modules\Driver\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Driver\Services\DriverRideService;
use App\Modules\Driver\Models\Ride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminRideController extends Controller
{
    protected $rideService;

    public function __construct(DriverRideService $rideService)
    {
        $this->rideService = $rideService;
    }

    /**
     * Display rides listing with filters
     */
    public function index(Request $request)
    {
        try {
            Log::info('AdminRideController: Loading rides index', $request->all());

            // Get filters from request
            $filters = $this->buildFilters($request);

            // Get rides data
            $ridesData = $this->rideService->getAllRides($filters);

            // Convert to collection and format for view
            $rides = collect($ridesData)->map(function ($ride) {
                return $this->formatRideForDisplay($ride);
            });

            // Calculate total rides count
            $totalRides = count($rides);

            // Get statistics
            $statistics = $this->rideService->getRideStatistics($filters);

            // Get summary data for dashboard cards
            $summaryData = $this->rideService->getRideSummaryData();

            // Get available filter options that match your view
            $rideStatuses = Ride::getRideStatuses();
            $rideTypes = Ride::getRideTypes();
            $paymentStatuses = Ride::getPaymentStatuses();

            // Get current filter values from request
            $search = $request->get('search', '');
            $status = $request->get('status', '');
            $ride_type = $request->get('ride_type', '');
            $payment_status = $request->get('payment_status', '');
            $date_from = $request->get('date_from', '');
            $date_to = $request->get('date_to', '');
            $limit = $request->get('limit', 50);

            Log::info('AdminRideController: Rides loaded successfully', [
                'rides_count' => $totalRides,
                'filters_applied' => $filters
            ]);

            return view('driver::admin.rides.index', compact(
                'rides',
                'totalRides',
                'statistics',
                'summaryData',
                'rideStatuses',
                'rideTypes',
                'paymentStatuses',
                'search',
                'status',
                'ride_type',
                'payment_status',
                'date_from',
                'date_to',
                'limit'
            ));
        } catch (\Exception $e) {
            Log::error('AdminRideController: Error loading rides: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('driver::admin.rides.index', [
                'rides' => collect([]),
                'totalRides' => 0,
                'statistics' => $this->getDefaultStatistics(),
                'summaryData' => $this->getDefaultSummaryData(),
                'rideStatuses' => Ride::getRideStatuses(),
                'rideTypes' => Ride::getRideTypes(),
                'paymentStatuses' => Ride::getPaymentStatuses(),
                'search' => $request->get('search', ''),
                'status' => $request->get('status', ''),
                'ride_type' => $request->get('ride_type', ''),
                'payment_status' => $request->get('payment_status', ''),
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
                'limit' => $request->get('limit', 50),
                'error' => 'Error loading ride dashboard: ' . $e->getMessage()
            ]);
        }
    }

    public function show(string $rideId)
    {
        try {
            Log::info('Admin viewing ride details', [
                'ride_id' => $rideId,
                'admin' => session('firebase_user.email') ?? 'unknown',
                'timestamp' => now()->toDateTimeString()
            ]);

            // Get ride data from service
            $rideData = $this->rideService->getRideById($rideId);

            if (!$rideData) {
                Log::warning('Ride not found', ['ride_id' => $rideId]);
                return redirect()->route('admin.rides.index')
                    ->with('error', 'Ride not found.');
            }

            // **CRITICAL FIX**: Ensure all data is properly formatted as scalars
            $ride = $this->sanitizeRideDataForView($rideData);

            Log::info('Ride details loaded successfully', [
                'ride_id' => $rideId,
                'status' => $ride['status'] ?? 'unknown'
            ]);

            return view('driver::admin.rides.show', [
                'ride' => $ride,
                'rideStatuses' => Ride::getRideStatuses(),
                'paymentStatuses' => Ride::getPaymentStatuses(),
                'cancellationReasons' => Ride::getCancellationReasons(),
                'rideTypes' => Ride::getRideTypes()
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading ride details: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'admin' => session('firebase_user.email') ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.rides.index')
                ->with('error', 'Error loading ride details: ' . $e->getMessage());
        }
    }

    /**
     * **NEW METHOD**: Sanitize ride data to prevent array-to-string errors
     */
    private function sanitizeRideDataForView($rideData): array
    {
        // Convert to array if it's an object
        if (!is_array($rideData)) {
            $rideData = is_object($rideData) && method_exists($rideData, 'toArray')
                ? $rideData->toArray()
                : (array)$rideData;
        }

        // Initialize with safe defaults
        $ride = [];

        // Basic information - ensure strings
        $ride['id'] = $this->ensureString($rideData['id'] ?? $rideData['ride_id'] ?? '');
        $ride['ride_id'] = $this->ensureString($rideData['ride_id'] ?? $ride['id']);
        $ride['status'] = $this->ensureString($rideData['status'] ?? 'pending');
        $ride['ride_type'] = $this->ensureString($rideData['ride_type'] ?? 'standard');
        $ride['payment_status'] = $this->ensureString($rideData['payment_status'] ?? 'pending');
        $ride['payment_method'] = $this->ensureString($rideData['payment_method'] ?? '');

        // User information - ensure strings
        $ride['passenger_name'] = $this->ensureString($rideData['passenger_name'] ?? 'Unknown Passenger');
        $ride['driver_name'] = $this->ensureString($rideData['driver_name'] ?? 'Unknown Driver');
        $ride['passenger_phone'] = $this->ensureString($rideData['passenger_phone'] ?? '');
        $ride['driver_phone'] = $this->ensureString($rideData['driver_phone'] ?? '');
        $ride['passenger_firebase_uid'] = $this->ensureString($rideData['passenger_firebase_uid'] ?? '');
        $ride['driver_firebase_uid'] = $this->ensureString($rideData['driver_firebase_uid'] ?? '');

        // Addresses - ensure strings and escape for JavaScript
        $ride['pickup_address'] = $this->sanitizeForJavaScript($rideData['pickup_address'] ?? 'Unknown pickup location');
        $ride['dropoff_address'] = $this->sanitizeForJavaScript($rideData['dropoff_address'] ?? 'Unknown dropoff location');

        // **CRITICAL**: Coordinates - ensure numeric values, never arrays
        $ride['pickup_latitude'] = $this->ensureNumeric($rideData['pickup_latitude'] ?? null, 37.7749);
        $ride['pickup_longitude'] = $this->ensureNumeric($rideData['pickup_longitude'] ?? null, -122.4194);
        $ride['dropoff_latitude'] = $this->ensureNumeric($rideData['dropoff_latitude'] ?? null, 37.7849);
        $ride['dropoff_longitude'] = $this->ensureNumeric($rideData['dropoff_longitude'] ?? null, -122.4094);

        // Financial data - ensure numeric
        $ride['estimated_fare'] = $this->ensureNumeric($rideData['estimated_fare'] ?? 0);
        $ride['actual_fare'] = $this->ensureNumeric($rideData['actual_fare'] ?? 0);
        $ride['distance_km'] = $this->ensureNumeric($rideData['distance_km'] ?? 0);
        $ride['duration_minutes'] = $this->ensureInteger($rideData['duration_minutes'] ?? 0);

        // Ratings - ensure numeric
        $ride['driver_rating'] = $this->ensureNumeric($rideData['driver_rating'] ?? 0);
        $ride['passenger_rating'] = $this->ensureNumeric($rideData['passenger_rating'] ?? 0);

        // Timestamps - ensure strings
        $ride['created_at'] = $this->ensureString($rideData['created_at'] ?? '');
        $ride['updated_at'] = $this->ensureString($rideData['updated_at'] ?? '');
        $ride['accepted_at'] = $this->ensureString($rideData['accepted_at'] ?? '');
        $ride['started_at'] = $this->ensureString($rideData['started_at'] ?? '');
        $ride['completed_at'] = $this->ensureString($rideData['completed_at'] ?? '');
        $ride['cancelled_at'] = $this->ensureString($rideData['cancelled_at'] ?? '');

        // Optional fields - ensure strings
        $ride['cancellation_reason'] = $this->ensureString($rideData['cancellation_reason'] ?? '');
        $ride['cancelled_by'] = $this->ensureString($rideData['cancelled_by'] ?? '');
        $ride['notes'] = $this->ensureString($rideData['notes'] ?? '');
        $ride['special_requests'] = $this->ensureString($rideData['special_requests'] ?? '');

        // Format dates for display
        $this->formatDatesForDisplay($ride);

        // Geocode missing coordinates if needed
        $this->ensureCoordinates($ride);

        return $ride;
    }

    /**
     * Ensure value is a string, convert arrays/objects to empty string
     */
    private function ensureString($value): string
    {
        if (is_array($value) || is_object($value)) {
            Log::warning('Array/object detected where string expected', ['value' => $value]);
            return '';
        }
        return (string)($value ?? '');
    }

    /**
     * Ensure value is numeric, return default if array/object
     */
    private function ensureNumeric($value, float $default = 0.0): float
    {
        if (is_array($value) || is_object($value)) {
            Log::warning('Array/object detected where numeric expected', ['value' => $value, 'using_default' => $default]);
            return $default;
        }

        $numeric = floatval($value ?? $default);

        // Validate coordinate ranges
        if ($default === 37.7749 || $default === -122.4194 || $default === 37.7849 || $default === -122.4094) {
            if ($numeric < -180 || $numeric > 180) {
                Log::warning('Invalid coordinate detected', ['coordinate' => $numeric, 'using_default' => $default]);
                return $default;
            }
        }

        return $numeric;
    }

    /**
     * Ensure value is integer
     */
    private function ensureInteger($value, int $default = 0): int
    {
        if (is_array($value) || is_object($value)) {
            Log::warning('Array/object detected where integer expected', ['value' => $value, 'using_default' => $default]);
            return $default;
        }
        return intval($value ?? $default);
    }

    /**
     * Sanitize string for safe JavaScript output
     */
    private function sanitizeForJavaScript(string $value): string
    {
        // Remove or escape problematic characters
        $value = str_replace(['"', "'", "\n", "\r", "\t"], ['\"', "\'", ' ', ' ', ' '], $value);
        return trim($value);
    }

    /**
     * Format dates for display
     */
    private function formatDatesForDisplay(array &$ride): void
    {
        $dateFields = ['created_at', 'accepted_at', 'started_at', 'completed_at', 'cancelled_at'];

        foreach ($dateFields as $field) {
            if (!empty($ride[$field])) {
                try {
                    $date = Carbon::parse($ride[$field]);
                    $ride[$field . '_formatted'] = $date->format('M d, Y g:i A');
                    $ride[$field . '_human'] = $date->diffForHumans();
                } catch (\Exception $e) {
                    Log::warning('Error parsing date field', [
                        'field' => $field,
                        'value' => $ride[$field],
                        'error' => $e->getMessage()
                    ]);
                    $ride[$field . '_formatted'] = $ride[$field];
                    $ride[$field . '_human'] = 'Unknown';
                }
            }
        }
    }

    /**
     * Ensure coordinates exist, geocode if necessary
     */
    private function ensureCoordinates(array &$ride): void
    {
        // Only geocode if coordinates are default values and we have addresses
        if (($ride['pickup_latitude'] == 37.7749 && $ride['pickup_longitude'] == -122.4194)
            && !empty($ride['pickup_address']) && $ride['pickup_address'] !== 'Unknown pickup location'
        ) {

            $coords = $this->geocodeAddress($ride['pickup_address']);
            if ($coords) {
                $ride['pickup_latitude'] = $coords['lat'];
                $ride['pickup_longitude'] = $coords['lng'];
            }
        }

        if (($ride['dropoff_latitude'] == 37.7849 && $ride['dropoff_longitude'] == -122.4094)
            && !empty($ride['dropoff_address']) && $ride['dropoff_address'] !== 'Unknown dropoff location'
        ) {

            $coords = $this->geocodeAddress($ride['dropoff_address']);
            if ($coords) {
                $ride['dropoff_latitude'] = $coords['lat'];
                $ride['dropoff_longitude'] = $coords['lng'];
            }
        }
    }

    /**
     * Geocode address to coordinates
     */
    private function geocodeAddress(string $address): ?array
    {
        $apiKey = config('services.google_maps.api_key') ?? env('GOOGLE_MAPS_API_KEY');

        if (empty($apiKey) || empty(trim($address))) {
            return null;
        }

        $cacheKey = 'geocode_' . md5($address);

        return cache()->remember($cacheKey, 1440, function () use ($address, $apiKey) {
            try {
                $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key={$apiKey}";

                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'user_agent' => 'RideApp/1.0'
                    ]
                ]);

                $response = file_get_contents($url, false, $context);

                if ($response === false) {
                    return null;
                }

                $data = json_decode($response, true);

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $location = $data['results'][0]['geometry']['location'];
                    return [
                        'lat' => floatval($location['lat']),
                        'lng' => floatval($location['lng'])
                    ];
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Geocoding error: ' . $e->getMessage());
                return null;
            }
        });
    }



    private function enhanceRideData(array $ride): array
    {
        Log::debug('Enhancing ride data', ['ride_id' => $ride['id'] ?? 'unknown']);

        // Ensure coordinates exist - geocode addresses if needed
        if (empty($ride['pickup_latitude']) && !empty($ride['pickup_address'])) {
            Log::info('Geocoding pickup address', ['address' => $ride['pickup_address']]);
            $pickupCoords = $this->geocodeAddress($ride['pickup_address']);
            if ($pickupCoords) {
                $ride['pickup_latitude'] = $pickupCoords['lat'];
                $ride['pickup_longitude'] = $pickupCoords['lng'];
            }
        }

        if (empty($ride['dropoff_latitude']) && !empty($ride['dropoff_address'])) {
            Log::info('Geocoding dropoff address', ['address' => $ride['dropoff_address']]);
            $dropoffCoords = $this->geocodeAddress($ride['dropoff_address']);
            if ($dropoffCoords) {
                $ride['dropoff_latitude'] = $dropoffCoords['lat'];
                $ride['dropoff_longitude'] = $dropoffCoords['lng'];
            }
        }

        // Ensure numeric coordinates with safe defaults
        $ride['pickup_latitude'] = $this->sanitizeCoordinate($ride['pickup_latitude'] ?? null, 37.7749);
        $ride['pickup_longitude'] = $this->sanitizeCoordinate($ride['pickup_longitude'] ?? null, -122.4194);
        $ride['dropoff_latitude'] = $this->sanitizeCoordinate($ride['dropoff_latitude'] ?? null, 37.7849);
        $ride['dropoff_longitude'] = $this->sanitizeCoordinate($ride['dropoff_longitude'] ?? null, -122.4094);

        // Format dates safely
        $this->formatRideDates($ride);

        // Ensure required fields with defaults
        $ride['status'] = $ride['status'] ?? 'pending';
        $ride['payment_status'] = $ride['payment_status'] ?? 'pending';
        $ride['ride_type'] = $ride['ride_type'] ?? 'standard';
        $ride['estimated_fare'] = floatval($ride['estimated_fare'] ?? 0);
        $ride['actual_fare'] = floatval($ride['actual_fare'] ?? 0);
        $ride['distance_km'] = floatval($ride['distance_km'] ?? 0);
        $ride['duration_minutes'] = intval($ride['duration_minutes'] ?? 0);
        $ride['driver_rating'] = floatval($ride['driver_rating'] ?? 0);
        $ride['passenger_rating'] = floatval($ride['passenger_rating'] ?? 0);

        // Clean up addresses for JavaScript safety
        $ride['pickup_address'] = $this->sanitizeAddress($ride['pickup_address'] ?? '');
        $ride['dropoff_address'] = $this->sanitizeAddress($ride['dropoff_address'] ?? '');

        // Get driver information if not already present
        if (!empty($ride['driver_firebase_uid']) && empty($ride['driver_name'])) {
            $ride = $this->enrichDriverInfo($ride);
        }

        // Get passenger information if not already present
        if (!empty($ride['passenger_firebase_uid']) && empty($ride['passenger_name'])) {
            $ride = $this->enrichPassengerInfo($ride);
        }

        // Calculate derived fields
        $ride = $this->calculateDerivedFields($ride);

        Log::debug('Ride data enhanced successfully', [
            'ride_id' => $ride['id'] ?? 'unknown',
            'has_pickup_coords' => !empty($ride['pickup_latitude']) && !empty($ride['pickup_longitude']),
            'has_dropoff_coords' => !empty($ride['dropoff_latitude']) && !empty($ride['dropoff_longitude'])
        ]);

        return $ride;
    }

    /**
     * Sanitize coordinate values
     */
    private function sanitizeCoordinate($coordinate, float $default): float
    {
        if ($coordinate === null || $coordinate === '' || $coordinate === 0) {
            return $default;
        }

        $coord = floatval($coordinate);

        // Basic validation for reasonable coordinate ranges
        if ($coord < -180 || $coord > 180) {
            Log::warning('Invalid coordinate detected', ['coordinate' => $coordinate, 'using_default' => $default]);
            return $default;
        }

        return $coord;
    }

    /**
     * Format ride dates safely
     */
    private function formatRideDates(array &$ride): void
    {
        $dateFields = ['created_at', 'accepted_at', 'started_at', 'completed_at', 'cancelled_at'];

        foreach ($dateFields as $field) {
            if (!empty($ride[$field])) {
                try {
                    $date = \Carbon\Carbon::parse($ride[$field]);
                    $ride[$field . '_formatted'] = $date->format('M d, Y g:i A');
                    $ride[$field . '_human'] = $date->diffForHumans();
                    $ride[$field . '_iso'] = $date->toISOString();
                } catch (\Exception $e) {
                    Log::warning('Error parsing date field', [
                        'field' => $field,
                        'value' => $ride[$field],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Sanitize address for JavaScript output
     */
    private function sanitizeAddress(string $address): string
    {
        return trim(str_replace(['"', "'", "\n", "\r"], ['\"', "\'", ' ', ' '], $address));
    }

    /**
     * Enrich ride with driver information
     */
    private function enrichDriverInfo(array $ride): array
    {
        try {
            $driver = $this->driverService->getDriverById($ride['driver_firebase_uid']);
            if ($driver) {
                $ride['driver_name'] = $driver['name'] ?? 'Unknown Driver';
                $ride['driver_phone'] = $driver['phone'] ?? null;
                $ride['driver_email'] = $driver['email'] ?? null;
                $ride['driver_status'] = $driver['status'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch driver information', [
                'driver_uid' => $ride['driver_firebase_uid'],
                'error' => $e->getMessage()
            ]);
            $ride['driver_name'] = 'Unknown Driver';
        }

        return $ride;
    }

    /**
     * Enrich ride with passenger information
     */
    private function enrichPassengerInfo(array $ride): array
    {
        try {
            // If you have a passenger service, use it here
            // $passenger = $this->passengerService->getPassengerById($ride['passenger_firebase_uid']);
            // For now, we'll use default values
            $ride['passenger_name'] = $ride['passenger_name'] ?? 'Unknown Passenger';
        } catch (\Exception $e) {
            Log::warning('Could not fetch passenger information', [
                'passenger_uid' => $ride['passenger_firebase_uid'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $ride['passenger_name'] = 'Unknown Passenger';
        }

        return $ride;
    }

    /**
     * Calculate derived fields
     */
    private function calculateDerivedFields(array $ride): array
    {
        // Calculate fare difference
        if ($ride['actual_fare'] > 0 && $ride['estimated_fare'] > 0) {
            $ride['fare_difference'] = $ride['actual_fare'] - $ride['estimated_fare'];
            $ride['fare_difference_percentage'] = ($ride['fare_difference'] / $ride['estimated_fare']) * 100;
        }

        // Calculate average speed if we have distance and duration
        if ($ride['distance_km'] > 0 && $ride['duration_minutes'] > 0) {
            $ride['average_speed_kmh'] = ($ride['distance_km'] / $ride['duration_minutes']) * 60;
        }

        // Determine primary fare (actual if available, otherwise estimated)
        $ride['primary_fare'] = $ride['actual_fare'] > 0 ? $ride['actual_fare'] : $ride['estimated_fare'];

        return $ride;
    }



    /**
     * Get driver statistics for display
     */


    /**
     * Get passenger information
     */
    private function getPassengerInfo(array $ride): ?array
    {
        if (empty($ride['passenger_firebase_uid'])) {
            return null;
        }

        try {
            // Cache passenger info for 10 minutes
            $cacheKey = "passenger_info_{$ride['passenger_firebase_uid']}";

            return cache()->remember($cacheKey, 600, function () use ($ride) {
                // You would implement this based on your passenger service
                return [
                    'firebase_uid' => $ride['passenger_firebase_uid'],
                    'name' => $ride['passenger_name'] ?? 'Unknown Passenger',
                    'phone' => $ride['passenger_phone'] ?? null,
                    'email' => $ride['passenger_email'] ?? null,
                    // Add more fields as needed
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting passenger info: ' . $e->getMessage(), [
                'passenger_uid' => $ride['passenger_firebase_uid']
            ]);
            return null;
        }
    }

    /**
     * Get ride activities/timeline
     */
    private function getRideActivities(string $rideId): array
    {
        try {
            // Cache ride activities for 2 minutes
            $cacheKey = "ride_activities_{$rideId}";

            return cache()->remember($cacheKey, 120, function () use ($rideId) {
                // This would come from your ride service or activity log
                // For now, we'll return an empty array
                // return $this->rideService->getRideActivities($rideId);
                return [];
            });
        } catch (\Exception $e) {
            Log::error('Error getting ride activities: ' . $e->getMessage(), [
                'ride_id' => $rideId
            ]);
            return [];
        }
    }

    /**
     * Update ride status
     */
    public function updateStatus(Request $request, string $rideId)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:' . implode(',', array_keys(Ride::getRideStatuses())),
                'notes' => 'nullable|string|max:500'
            ]);

            Log::info('AdminRideController: Updating ride status', [
                'ride_id' => $rideId,
                'status' => $request->status
            ]);

            $additionalData = [];
            if ($request->filled('notes')) {
                $additionalData['admin_notes'] = $request->notes;
            }
            $additionalData['updated_by_admin'] = true;

            $success = $this->rideService->updateRideStatus(
                $rideId,
                $request->status,
                $additionalData
            );

            if ($success) {
                Log::info('AdminRideController: Ride status updated successfully', [
                    'ride_id' => $rideId,
                    'status' => $request->status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ride status updated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update ride status'
                ], 400);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AdminRideController: Error updating ride status: ' . $e->getMessage(), [
                'ride_id' => $rideId,
                'status' => $request->status ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating ride status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete ride
     */
    public function complete(Request $request, string $rideId)
    {
        try {
            $completionData = [];

            if ($request->filled('actual_fare')) {
                $completionData['actual_fare'] = $request->actual_fare;
            }

            if ($request->filled('notes')) {
                $completionData['completion_notes'] = $request->notes;
            }

            Log::info('AdminRideController: Completing ride', [
                'ride_id' => $rideId,
                'completion_data' => $completionData
            ]);

            $success = $this->rideService->completeRide($rideId, $completionData);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ride completed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to complete ride'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('AdminRideController: Error completing ride: ' . $e->getMessage(), [
                'ride_id' => $rideId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error completing ride: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel ride
     */
    public function cancel(Request $request, string $rideId)
    {
        try {
            $request->validate([
                'cancellation_reason' => 'required|string|max:500'
            ]);

            Log::info('AdminRideController: Cancelling ride', [
                'ride_id' => $rideId,
                'reason' => $request->cancellation_reason
            ]);

            $success = $this->rideService->cancelRide(
                $rideId,
                $request->cancellation_reason,
                'admin',
                ['cancelled_by_admin' => true]
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ride cancelled successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cancel ride'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('AdminRideController: Error cancelling ride: ' . $e->getMessage(), [
                'ride_id' => $rideId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error cancelling ride: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export rides data
     */
    public function export(Request $request)
    {
        try {
            $filters = $this->buildFilters($request);
            $filters['limit'] = 10000; // Export limit

            Log::info('AdminRideController: Exporting rides', ['filters' => $filters]);

            $rides = $this->rideService->getAllRides($filters);

            // Format data for CSV export
            $csvData = collect($rides)->map(function ($ride) {
                return [
                    'Ride ID' => $this->safeString($ride['ride_id'] ?? $ride['id'] ?? ''),
                    'Driver UID' => $this->safeString($ride['driver_firebase_uid'] ?? ''),
                    'Passenger Name' => $this->safeString($ride['passenger_name'] ?? ''),
                    'Pickup Address' => $this->safeString($ride['pickup_address'] ?? ''),
                    'Dropoff Address' => $this->safeString($ride['dropoff_address'] ?? ''),
                    'Status' => $this->safeString($ride['status'] ?? ''),
                    'Ride Type' => $this->safeString($ride['ride_type'] ?? ''),
                    'Estimated Fare' => $this->safeNumber($ride['estimated_fare'] ?? 0),
                    'Actual Fare' => $this->safeNumber($ride['actual_fare'] ?? 0),
                    'Distance (KM)' => $this->safeNumber($ride['distance_km'] ?? 0),
                    'Duration (Min)' => $this->safeNumber($ride['duration_minutes'] ?? 0),
                    'Driver Rating' => $this->safeNumber($ride['driver_rating'] ?? 0),
                    'Passenger Rating' => $this->safeNumber($ride['passenger_rating'] ?? 0),
                    'Payment Status' => $this->safeString($ride['payment_status'] ?? ''),
                    'Created At' => $this->safeString($ride['created_at'] ?? ''),
                    'Completed At' => $this->safeString($ride['completed_at'] ?? ''),
                    'Cancelled At' => $this->safeString($ride['cancelled_at'] ?? ''),
                    'Cancellation Reason' => $this->safeString($ride['cancellation_reason'] ?? ''),
                ];
            })->toArray();

            // Create CSV response
            $filename = 'rides_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($csvData) {
                $file = fopen('php://output', 'w');

                // Add CSV headers
                if (!empty($csvData)) {
                    fputcsv($file, array_keys($csvData[0]));
                }

                // Add data rows
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('AdminRideController: Error exporting rides: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error exporting rides: ' . $e->getMessage());
        }
    }

    // ===================== PRIVATE HELPER METHODS =====================

    /**
     * Build filters from request parameters
     */
    private function buildFilters(Request $request): array
    {
        $filters = [];

        // Status filter
        if ($request->filled('status')) {
            $filters['status'] = $request->status;
        }

        // Ride type filter
        if ($request->filled('ride_type')) {
            $filters['ride_type'] = $request->ride_type;
        }

        // Payment status filter
        if ($request->filled('payment_status')) {
            $filters['payment_status'] = $request->payment_status;
        }

        // Driver filter
        if ($request->filled('driver_uid')) {
            $filters['driver_firebase_uid'] = $request->driver_uid;
        }

        // Search term
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }

        // Date range filters
        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->date_from;
        }

        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->date_to;
        }

        // Pagination
        $filters['limit'] = $request->get('limit', 50);

        return $filters;
    }

    /**
     * Format ride data for display (creates array to avoid Collection issues)
     */
    private function formatRideForDisplay($ride): array
    {
        // Ensure we're working with an array
        if (!is_array($ride)) {
            $ride = is_object($ride) && method_exists($ride, 'toArray') ? $ride->toArray() : (array)$ride;
        }

        // Create a new formatted array
        $formatted = [];

        // Basic ride information
        $formatted['id'] = $this->safeString($ride['id'] ?? ($ride['ride_id'] ?? uniqid('ride_')));
        $formatted['ride_id'] = $this->safeString($ride['ride_id'] ?? $formatted['id']);
        $formatted['driver_firebase_uid'] = $this->safeString($ride['driver_firebase_uid'] ?? '');
        $formatted['passenger_firebase_uid'] = $this->safeString($ride['passenger_firebase_uid'] ?? '');
        $formatted['passenger_name'] = $this->safeString($ride['passenger_name'] ?? 'Unknown Passenger');
        $formatted['driver_name'] = $this->safeString($ride['driver_name'] ?? 'Unknown Driver');

        // Addresses
        $formatted['pickup_address'] = $this->safeString($ride['pickup_address'] ?? 'Unknown Pickup');
        $formatted['dropoff_address'] = $this->safeString($ride['dropoff_address'] ?? 'Unknown Dropoff');

        // Status and type
        $formatted['status'] = $this->safeString($ride['status'] ?? Ride::STATUS_PENDING);
        $formatted['ride_type'] = $this->safeString($ride['ride_type'] ?? Ride::TYPE_STANDARD);

        // Financial information
        $formatted['estimated_fare'] = $this->safeNumber($ride['estimated_fare'] ?? 0);
        $formatted['actual_fare'] = $this->safeNumber($ride['actual_fare'] ?? 0);
        $formatted['payment_status'] = $this->safeString($ride['payment_status'] ?? Ride::PAYMENT_PENDING);
        $formatted['payment_method'] = $this->safeString($ride['payment_method'] ?? '');

        // Trip details
        $formatted['distance_km'] = $this->safeNumber($ride['distance_km'] ?? 0);
        $formatted['duration_minutes'] = $this->safeNumber($ride['duration_minutes'] ?? 0);

        // Ratings
        $formatted['driver_rating'] = $this->safeNumber($ride['driver_rating'] ?? 0);
        $formatted['passenger_rating'] = $this->safeNumber($ride['passenger_rating'] ?? 0);

        // Timestamps
        $formatted['created_at'] = $this->safeString($ride['created_at'] ?? now()->format('Y-m-d H:i:s'));
        $formatted['updated_at'] = $this->safeString($ride['updated_at'] ?? now()->format('Y-m-d H:i:s'));
        $formatted['completed_at'] = $this->safeString($ride['completed_at'] ?? '');
        $formatted['cancelled_at'] = $this->safeString($ride['cancelled_at'] ?? '');

        // Additional fields
        $formatted['cancellation_reason'] = $this->safeString($ride['cancellation_reason'] ?? '');
        $formatted['cancelled_by'] = $this->safeString($ride['cancelled_by'] ?? '');
        $formatted['notes'] = $this->safeString($ride['notes'] ?? '');
        $formatted['special_requests'] = $this->safeString($ride['special_requests'] ?? '');
        $formatted['passenger_phone'] = $this->safeString($ride['passenger_phone'] ?? '');

        return $formatted;
    }

    /**
     * Format ride data specifically for the show view to prevent array display issues
     */
    private function formatRideForShowView($rideData): array
    {
        // Start with the basic formatting
        $ride = $this->formatRideForDisplay($rideData);

        // Additional formatting specifically for show view
        $ride['status_label'] = ucfirst(str_replace('_', ' ', $ride['status']));
        $ride['ride_type_label'] = ucfirst(str_replace('_', ' ', $ride['ride_type']));
        $ride['payment_status_label'] = ucfirst(str_replace('_', ' ', $ride['payment_status']));

        // Format currency values
        $ride['estimated_fare_formatted'] = '$' . number_format($ride['estimated_fare'], 2);
        $ride['actual_fare_formatted'] = '$' . number_format($ride['actual_fare'], 2);

        // Format distances and durations
        $ride['distance_formatted'] = number_format($ride['distance_km'], 2) . ' km';
        $ride['duration_formatted'] = $ride['duration_minutes'] . ' minutes';

        // Format timestamps for display
        if ($ride['created_at']) {
            try {
                $ride['created_at_formatted'] = Carbon::parse($ride['created_at'])->format('M d, Y H:i');
                $ride['created_at_human'] = Carbon::parse($ride['created_at'])->diffForHumans();
            } catch (\Exception $e) {
                $ride['created_at_formatted'] = $ride['created_at'];
                $ride['created_at_human'] = 'Unknown';
            }
        } else {
            $ride['created_at_formatted'] = 'Unknown';
            $ride['created_at_human'] = 'Unknown';
        }

        return $ride;
    }

    /**
     * Format driver stats for view to prevent array display issues
     */
    private function formatDriverStatsForView($statsData): array
    {
        if (!is_array($statsData)) {
            $statsData = is_object($statsData) && method_exists($statsData, 'toArray') ? $statsData->toArray() : [];
        }

        return [
            'total_rides' => $this->safeNumber($statsData['total_rides'] ?? 0),
            'completed_rides' => $this->safeNumber($statsData['completed_rides'] ?? 0),
            'cancelled_rides' => $this->safeNumber($statsData['cancelled_rides'] ?? 0),
            'completion_rate' => $this->safeNumber($statsData['completion_rate'] ?? 0),
            'cancellation_rate' => $this->safeNumber($statsData['cancellation_rate'] ?? 0),
            'average_rating' => $this->safeNumber($statsData['average_rating'] ?? 0),
            'total_earnings' => $this->safeNumber($statsData['total_earnings'] ?? 0),
            'total_distance' => $this->safeNumber($statsData['total_distance'] ?? 0),
        ];
    }

    /**
     * Safely convert value to string
     */
    private function safeString($value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return (string)$value;
    }

    /**
     * Safely convert value to number
     */
    private function safeNumber($value): float
    {
        if (is_array($value) || is_object($value)) {
            return 0.0;
        }
        return (float)$value;
    }

    /**
     * Get default statistics when there's an error
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
            'completion_rate' => 0,
            'cancellation_rate' => 0
        ];
    }

    /**
     * Get default summary data when there's an error
     */
    private function getDefaultSummaryData(): array
    {
        return [
            'today' => [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'cancelled' => 0
            ],
            'this_week' => [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'cancelled' => 0
            ],
            'this_month' => [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'cancelled' => 0
            ]
        ];
    }
}
